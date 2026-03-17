<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Http\Session;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use ControllerAction\Model\Traits\UtilityTrait;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\Table; // POCOR-8128
use Cake\Utility\Inflector; // POCOR-8128

class StaffPositionTitlesTable extends ControllerActionTable
{
	use UtilityTrait;
	use OptionsTrait;

	CONST SELECT_POSITION_GRADES = 1;
	CONST SELECT_ALL_POSITION_GRADES = '-1';

	private $positionGradeSelection = [];

	public function initialize(array $config): void
	{
        $this->setTable('staff_position_titles');
        parent::initialize($config);
        $this->hasMany('TrainingCoursesTargetPopulations', ['className' => 'Training.TrainingCoursesTargetPopulations', 'foreignKey' => 'target_population_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);
        $this->belongsTo('LeavePolicies', ['className' => 'System.LeavePolicies', 'foreignKey' => 'staff_leave_policy_id']); // POCOR-8128
        $this->hasMany('InstitutionPositions', ['className' => 'Institution.InstitutionPositions', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->belongsToMany('PositionGrades', [
			'className' => 'Institution.StaffPositionGrades',
			'joinTable' => 'staff_position_titles_grades',
			'foreignKey' => 'staff_position_title_id',
			'targetForeignKey' => 'staff_position_grade_id',
			'through' => 'Institution.StaffPositionTitlesGrades',
			'dependent' => true,
			'cascadeCallbacks' => true
		]);

        $this->addBehavior('FieldOption.FieldOption');
		$this->addBehavior('ControllerAction.FileUpload', [//POCOR-7758
			'name' => 'file_name',
			'content' => 'file_content',
			'size' => '10MB',
			'contentEditable' => true,
			'allowable_file_types' => 'doc/pdf',
			'useDefaultName' => true
		]);
		$this->positionGradeSelection = $this->getSelectOptions($this->aliasField('position_grade_selection'));

	}

	public function validationDefault(Validator $validator): Validator
	{
		$validator = parent::validationDefault($validator);
		$validator->setProvider('custom', $this);
		return $validator
			->requirePresence('position_grades')
		    ->allowEmptyString('file_content')//POCOR-7758
            ->requirePresence('security_role_id') // POCOR-9508-start
            ->notEmptyString('security_role_id')
            ->requirePresence('staff_leave_policy_id')
            ->notEmptyString('staff_leave_policy_id')
            ->requirePresence('staff_position_categories_id')
            ->notEmptyString('staff_position_categories_id') // POCOR-9508-end
			->add('position_grade_selection', 'ruleCheckPositionGrades', [
				'rule' => ['checkPositionGrades'],
				'provider' => 'table',
				'on' => function ($context) {
				//trigger validation only when position grade selection is set to 1	 and edit operation
				return ($context['data']['position_grade_selection'] == self::SELECT_POSITION_GRADES
                    && !$context['newRecord']);
			}
		]);
	}

	public function beforeAction(EventInterface $event, ArrayObject $extra) {
		$this->field('type', [
			'visible' => true,
			'options' => $this->getSelectOptions('Staff.position_types'),
			'after' => 'name',
			'onChangeReload' => true
		]);
        // POCOR-8128 start
		$this->field('staff_leave_policy_id', [
			'visible' => true,
            'type' => 'select',
            'select' => false,
			'options' => $this->getStaffLeavePolicyOptions(),
			'after' => 'default',
            'attr' => ['required' => true], // to add red asterisk
		]);
		$this->field('staff_position_categories_id', [
			'visible' => true,
            'type' => 'select',
            'select' => false,
			'options' => $this->getStaffPositionCategoryOptions(),
			'after' => 'type',
            'attr' => ['required' => true], // to add red asterisk
		]);
        // POCOR-8128 end

		$extra['roleList'] = $this->SecurityRoles->getSystemRolesList();
		$this->field('security_role_id', ['after' => 'staff_position_categories_id', 'options' => $extra['roleList']]);
	}

	public function indexBeforeAction(EventInterface $event, ArrayObject $extra) {
		if ($this->Session->check('StaffPositionTitles.error')) {
			$this->Alert->error($this->Session->read('StaffPositionTitles.error'), ['reset' => true]);
			$this->Session->delete('StaffPositionTitles.error');
		}
		$this->field('type', ['after' => 'name']);
		$this->field('security_role_id', ['after' => 'type']);
		$this->field('file_content', ['after' => 'position_grades', 'attr' => ['label' => __('Description')], 'visible' => ['add' => true, 'view' => true, 'edit' => true, 'index' => false]]);//POCOR-7758
        $this->field('file_name',['visible'=>false]);//POCOR-7758
	}

	public function addOnInitialize(EventInterface $event, Entity $entity, ArrayObject $extra)
	{
		$entity->position_grade_selection = self::SELECT_POSITION_GRADES;
	}

	public function addAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
	{
		$this->field('file_name', ['visible' => false]);//POCOR-7758
		$this->field('file_content', ['attr' => ['label' => __('Description')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);//POCOR-7758

		$this->setupFields($entity);
	}

	public function addEditBeforePatch(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
	{
		// POCOR-8777
		$requestDataArray = $requestData->getArrayCopy();

		if (array_key_exists($this->getAlias(), $requestDataArray)) {
			if (isset($requestDataArray[$this->getAlias()]['position_grades']['_ids'])
                && empty($requestDataArray[$this->getAlias()]['position_grades']['_ids'])) {
				$requestDataArray[$this->getAlias()]['position_grades'] = [];
			}
		}
	}


    public function editOnInitialize(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
//        Log::debug('[editOnInitialize] Entity ID: ' . $entity->id);

        $isSelectAll = $this->checkIsSelectAll($entity);
//        Log::debug('[editOnInitialize] checkIsSelectAll = ' . json_encode($isSelectAll));

        if ($isSelectAll) {
            $entity->position_grade_selection = self::SELECT_ALL_POSITION_GRADES;
//            Log::debug('[editOnInitialize] Setting position_grade_selection = SELECT_ALL');
        } else {
            $entity->position_grade_selection = self::SELECT_POSITION_GRADES;
//            Log::debug('[editOnInitialize] Setting position_grade_selection = SELECT_POSITION');
        }
    }

	public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra) {

		$this->field('file_name', ['visible' => false]);//POCOR-7758
		$this->field('file_content', ['attr' => ['label' => __('Description')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);//POCOR-7758

		$this->setupFields($entity);

		$titleId = $entity->id;
		$errorProcess = $this->checkIfError($titleId);
		if ($this->checkIfRunning($titleId)) {
			$urlParams = $this->url('index');
			$this->Session->write('StaffPositionTitles.error', 'StaffPositionTitles.inProgress');
			$event->stopPropagation();
			return $this->controller->redirect($urlParams);
		} else if ($errorProcess) {
			$urlParams = $this->url('index');
			$event = $this->dispatchEvent('Shell.shellRestartUpdateRole', [$errorProcess['id'], $errorProcess['executed_count'], $errorProcess['params']]);
			$this->Session->write('StaffPositionTitles.error', 'StaffPositionTitles.error');
			$event->stopPropagation();
			return $this->controller->redirect($urlParams);
		}
	}

	public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
	{
		$this->field('file_name', ['visible' => false]);//POCOR-7758
		$this->field('file_content', ['attr' => ['label' => __('Description')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);//POCOR-7758
		$this->setupFields($entity);
	}

	public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
	{
		$query->contain(['PositionGrades']);
	}

	/**
     * Get all areas ids as key and name as value
     * @usage  It is used as drop-down options
     * @author Rahul Singh <rahul.singh@mail.valuecoders.com>
     * @ticket POCOR-6950
     */
// // POCOR-8128
//	public function onUpdateFieldStaffPositionCategoriesId(EventInterface $event, array $attr, $action, ServerRequest $request)
//	{
//		$request = $this->request;
//        if ($action == 'add' || $action == 'edit') {
//        	list($levelOptions, $selectedLevel) = array_values($this->getTypeOptions($request, $action));//POCOR-7292 add param action
//        	$attr['options'] = $levelOptions;
//        	if ($action == 'add') {
//        		$attr['default'] = $selectedLevel;
//        	}else if($action == 'edit'){//POCOR-7292 starts
//        		$typeId= $this->paramsDecode($request->getAttribute('params')['pass'][1]);
//        		$StaffPositionTitles = TableRegistry::getTableLocator()->get('Institution.StaffPositionTitles');
//        		$Options = $StaffPositionTitles
//					            ->find()
//					            ->where([$StaffPositionTitles->aliasField('id') => $typeId['id']])
//					            ->first();
//				$attr['value'] = $levelOptions[$Options->staff_position_categories_id];
//        	}//POCOR-7292 ends
//        }
//		return $attr;
//	}

	/**
     * Get all areas ids as key and name as value
     * @usage  It is used as drop-down options
     * @author Rahul Singh <rahul.singh@mail.valuecoders.com>
     * @ticket POCOR-6950
     */

	public function getTypeOptions($request, $action = null)//POCOR-7292 add param $action
    {
		$type = $this->request->getData('StaffPositionTitles')['type'];
		$StaffPositionCategories = TableRegistry::getTableLocator()->get('Staff.StaffPositionCategories');

		// POCOR-8777
		$whereCondition = is_null($type) ? [$StaffPositionCategories->aliasField('type') . ' IS NULL'] : [$StaffPositionCategories->aliasField('type') => $type];

        //POCOR-7292 starts
        if($action == 'edit'){
    		$StaffPositionTitlesPass= $this->paramsDecode($this->request->getAttribute('params')['pass'][1]);
    		$StaffPositionTitles = TableRegistry::getTableLocator()->get('Institution.StaffPositionTitles');
    		$Options = $StaffPositionTitles
				->find()
				->where([$StaffPositionTitles->aliasField('id') => $StaffPositionTitlesPass['id']])
				->first();
			$type = !empty($Options) ? $Options->type : '';
		}//POCOR-7292 ends
		$levelOptions = $StaffPositionCategories
			->find('list', ['keyField' => 'id', 'valueField' => 'name'])
			->where($whereCondition)  // POCOR-8777
			->toArray();

         $selectedLevel = !is_null($this->request->getQuery('level')) ? $this->request->getQuery('level') : key($levelOptions);
         return compact('levelOptions', 'selectedLevel');
    }

	public function onUpdateFieldPositionGradeSelection(EventInterface $event, array $attr, $action, ServerRequest $request)
	{
		if ($action == 'add' || $action == 'edit') {
			$attr['options'] = $this->positionGradeSelection;
			$attr['select'] = false;
			$attr['onChangeReload'] = true;
		}
		return $attr;
	}

	public function onUpdateFieldPositionGrades(EventInterface $event, array $attr, $action, ServerRequest $request)
	{
		$requestData = $this->request->getData();
		$entity = $attr['entity'];
		$staffPositionGradeOptions = TableRegistry::getTableLocator()->get('Institution.StaffPositionGrades')->getList()->toArray();

		$positionGradeSelection = null;
		if (isset($requestData[$this->getAlias()]['position_grade_selection'])) {
			$positionGradeSelection = $requestData[$this->getAlias()]['position_grade_selection'];
		} else {
			$positionGradeSelection = $entity->position_grade_selection;
		}

		if ($positionGradeSelection == self::SELECT_ALL_POSITION_GRADES) {
			$attr['value'] = self::SELECT_ALL_POSITION_GRADES;
			$attr['attr']['value'] = __('All Position Grades Selected');
			$attr['type'] = 'readonly';
		} else {
			$attr['options'] = $staffPositionGradeOptions;
		}

		return $attr;
	}

	public function setupFields(Entity $entity)
	{
		$this->field('position_grade_selection', [
			'type' => 'select',
			'visible' => ['index' => false, 'view' => false, 'edit' => true, 'add' => true],
			'entity' => $entity,
			'after' => 'security_role_id'
		]);
		$this->field('position_grades', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Position Grades'),
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true],
			'attr' => ['required' => true], // to add red asterisk
			'entity' => $entity,
			'after' => 'position_grade_selection'
		]);
		$this->field('file_content', ['after' => 'position_grades',
            'attr' => ['label' => __('Description')], 'visible' => ['add' => true, 'view' => true, 'edit' => true,'index'=>false]]);//POCOR-7758

	}

    public function onGetPositionGrades(EventInterface $event, Entity $entity)
    {
        $isSelectAll = $this->checkIsSelectAll($entity);

        if ($this->action == 'view' && $isSelectAll) {
            $StaffPositionTitles = TableRegistry::getTableLocator()->get('Institution.StaffPositionGrades');
            $list = $StaffPositionTitles
                ->find('list')
                ->find('order')
                ->toArray();

            return (!empty($list))? implode(', ', $list) : ' ';
        }
    }
    public function onGetType(EventInterface $event, Entity $entity)
	{
		$types = $this->getSelectOptions('Staff.position_types');
		return array_key_exists($entity->type, $types) ? $types[$entity->type] : $entity->type;
	}

	/**
     * Get the value and name of Staff Position Categories
     * @usage  It is used as drop-down options
     * @author Rahul Singh <rahul.singh@mail.valuecoders.com>
     * @ticket POCOR-6950
     */

	public function onGetStaffPositionCategoriesId(EventInterface $event, Entity $entity)
	{
		$StaffPositionCategories = TableRegistry::getTableLocator()->get('Staff.StaffPositionCategories');
            $list = $StaffPositionCategories
                ->find('list')
                ->find('order')
                ->where([ $StaffPositionCategories->aliasField('id') => $entity->staff_position_categories_id ])
                ->toArray();

            return (!empty($list))? implode(', ', $list) : ' ';
	}

	public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
	{
		$this->setAllPositionGrades($entity);

		//POCOR-9588
		if (!$entity->isNew() && $entity->getOriginal('security_role_id') != $entity->security_role_id) {
		    $oldRoleId = $entity->getOriginal('security_role_id');
		    $newRoleId = $entity->security_role_id;
		    $titleId = $entity->id;
		    $this->startUpdateRoles($newRoleId, $titleId);
		}
	}

    private function setAllPositionGrades(Entity $entity): void
    {
//        Log::debug('[setAllPositionGrades] Entity ID: ' . $entity->id);
//        Log::debug('[setAllPositionGrades] position_grade_selection: ' . json_encode($entity->position_grade_selection ?? null));

        if (
            $entity->has('position_grade_selection') &&
            $entity->position_grade_selection == self::SELECT_ALL_POSITION_GRADES
        ) {
            $StaffPositionTitlesGrades = TableRegistry::getTableLocator()->get('Institution.StaffPositionTitlesGrades');
            $entityId = $entity->id;

//            Log::debug('[setAllPositionGrades] Deleting all existing grades for this title');
            $StaffPositionTitlesGrades->deleteAll([
                'staff_position_title_id' => $entityId
            ]);

            $data = [
                'staff_position_title_id' => $entityId,
                'staff_position_grade_id' => self::SELECT_ALL_POSITION_GRADES
            ];

//            Log::debug('[setAllPositionGrades] Inserting SELECT_ALL row: ' . json_encode($data));

            $newEntity = $StaffPositionTitlesGrades->newEntity($data);

            if (!$StaffPositionTitlesGrades->save($newEntity)) {
                Log::debug('[setAllPositionGrades] Save failed: ' . json_encode($newEntity->getErrors()));
            }
//            else {
//                Log::debug('[setAllPositionGrades] SELECT_ALL row saved successfully.');
//            }
        }
//        else {
//            Log::debug('[setAllPositionGrades] Skipped — position_grade_selection is not SELECT_ALL');
//        }
    }



    public function checkIsSelectAll($entity)
    {
        $StaffPositionTitlesGrades = TableRegistry::getTableLocator()->get('Institution.StaffPositionTitlesGrades');

        $isSelectAll = $StaffPositionTitlesGrades
            ->find()
            ->where([
                $StaffPositionTitlesGrades->aliasField('staff_position_title_id') => $entity->id,
                $StaffPositionTitlesGrades->aliasField('staff_position_grade_id') => self::SELECT_ALL_POSITION_GRADES
            ])
            ->count();

        return $isSelectAll;
    }

	private function startUpdateRoles($newRoleId, $titleId, $systemProcessId = null, $executedCount = null) {
		$cmd = ROOT . DS . 'bin' . DS . 'cake UpdateStaffRoles '.$newRoleId.' '.$titleId;

		if (!is_null($systemProcessId)) {
			$cmd .= ' '.$systemProcessId;
			$cmd .= ' '.$executedCount;
		}

		$logs = ROOT . DS . 'logs' . DS . 'UpdateStaffRoles.log & echo $!';
		$shellCmd = $cmd . ' >> ' . $logs;

		try {
			$pid = exec($shellCmd);
			Log::write('debug', $shellCmd);
		} catch(\Exception $ex) {
			Log::write('error', __METHOD__ . ' exception when removing inactive roles : '. $ex);
		}
	}

	public function checkIfRunning($titleId) {
		$SystemProcesses = TableRegistry::getTableLocator()->get('SystemProcesses');
		$runningProcess = $SystemProcesses->getRunningProcesses('Institution.StaffPositionTitles');
		foreach ($runningProcess as $process) {
			$param = json_decode($process['params']);
			if ($param->titleId == $titleId) {
				return true;
			}
		}
		return false;
	}

	public function checkIfError($titleId) {
		$SystemProcesses = TableRegistry::getTableLocator()->get('SystemProcesses');
		$runningProcess = $SystemProcesses->getErrorProcesses('Institution.StaffPositionTitles');
		foreach ($runningProcess as $process) {
			$param = json_decode($process['params']);
			if ($param->titleId == $titleId) {
				return $process;
			}
		}
		return false;
	}

	public function implementedEvents(): array {
		$events = parent::implementedEvents();
		$events['Shell.shellRestartUpdateRole'] = 'shellRestartUpdateRole';
		return $events;
	}

	public function shellRestartUpdateRole(EventInterface $event, $systemProcessId, $executedCount, $params) {
		$decodedParam = json_decode($params);
		$newRoleId = $decodedParam->newRoleId;
		$titleId = $decodedParam->titleId;
		if (!$this->checkIfRunning($titleId)) {
			$entity = $this->find()->where([$this->aliasField('id') => $titleId])->first();
			if (!empty($entity) && $entity->security_role_id == $newRoleId) {
				$this->startUpdateRoles($newRoleId, $titleId, $systemProcessId, $executedCount);
			}
		}
	}

	public function securityRolesUpdates($newRoleId, $titleId) {
		$SecurityGroupUsersTable = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
		$InstitutionStaffTable = TableRegistry::getTableLocator()->get('Institution.Staff');

		while (true) {
			$subQuery = $InstitutionStaffTable->find()
				->innerJoinWith('Positions.StaffPositionTitles', function($q) use ($titleId) {
					return $q->where(['StaffPositionTitles.id' => $titleId]);
				})
				->innerJoinWith('SecurityGroupUsers')
				->where([
					$InstitutionStaffTable->aliasField('security_group_user_id').' IS NOT NULL',
					'SecurityGroupUsers.security_role_id <> ' => $newRoleId
				])
				->where([
					'OR' => [
						[function ($exp) use ($InstitutionStaffTable) {
							return $exp->gte($InstitutionStaffTable->aliasField('end_date'), $InstitutionStaffTable->find()->func()->now('date'));
						}],
						[$InstitutionStaffTable->aliasField('end_date').' IS NULL']
					]
				])
				->select([
					'security_group_user_id' => $InstitutionStaffTable->aliasField('security_group_user_id'),
					'staff_id' => $InstitutionStaffTable->aliasField('staff_id')
				])
				->limit(1000)
				->page(1);

			$updateSubQuery = $this->query()
				->select(['security_group_user_id' => 'GroupUsers.security_group_user_id', 'staff_id' => 'GroupUsers.staff_id'])
				->from(['GroupUsers' => $subQuery]);

			$resultSet = $updateSubQuery->all();

			if ($resultSet->count() == 0) {
				break;
			} else {
				foreach ($resultSet as $entity) {
					Log::write('debug', __FUNCTION__ . ' - Updating roles for user_id (' . $entity->staff_id . ')');
					$SecurityGroupUsersTable->updateAll(
						['security_role_id' => $newRoleId],
						['id' => $entity->security_group_user_id]);
				}
			}
		}
	}

	/**
     * Get the code of Staff according to Position
     * @usage  Used to fetch principal and vice principal code
     * @author Prajakta K
     * @ticket POCOR-8093
     */
	// public function getPrincipalRoleId()
    // {
    //     $principalData = $this->find()
    //         ->select([$this->getPrimaryKey()])
    //         ->where([$this->aliasField('name') => 'Principal'])
    //         ->first();

    //     return (!empty($principalData))? $principalData->id: null;
    // }


	/**
     * Get the code of Staff according to Position
     * @usage  Used to fetch principal and vice principal code
     * @author Ehteram
     * @ticket POCOR-9208
	 * Reason to update this code; Principal may have diffrent name in some ENV
     */
	public function getPrincipalRoleId_old()
    {
        $principalData = $this->find()
            ->select([$this->getPrimaryKey()])
            ->where([$this->aliasField('name') => 'Principal'])
            ->first();
		if(empty($principalData)){
			$principalData = $this->find()
				->select([$this->getPrimaryKey()])
				->where([$this->aliasField('name') => 'Deputy Principal - Non-Teaching'])
				->first();
		}
		if(empty($principalData)){
			$principalData = $this->find()
            ->select([$this->getPrimaryKey()])
			->where([$this->aliasField('name') => 'Deputy Principal - Teaching'])
            ->first();
		}

		if(empty($principalData)){
			$principalData = $this->find()
            ->select([$this->getPrimaryKey()])
			->where([$this->aliasField('name') => 'Principal - teaching'])
            ->first();
		}

        return (!empty($principalData))? $principalData->id: null;
    }

	/**
     * @usage  Used to fetch principal code based on security role id and name
      * @author Prajakta
     * @ticket POCOR-9413
	 * @ticket POCOR-9442
	 * Reason to update this code: Principal may have diffrent name in some ENV and also security role id is mandatory to fetch correct principal role
     */
	public function getPrincipalRoleId($staffRoleId = null)
	{
		$query = $this->find()
			->select([$this->getPrimaryKey()])
			->where(function ($exp, $q) {
				return $exp->like($this->aliasField('name'), '%Principal%');
			});

		if (!empty($staffRoleId)) {
			$query->andWhere([$this->aliasField('security_role_id') => $staffRoleId]);
		}

		$principalData = $query->all();

		return !empty($principalData) ? $principalData->extract('id')->toList() : [];
	}

	public function getDeputyPrincipalRoleId()
    {
        $deputyPrincipalData = $this->find()
            ->select([$this->getPrimaryKey()])
            ->where([$this->aliasField('name') => 'Vice Principal'])
            ->first();

        return (!empty($deputyPrincipalData))? $deputyPrincipalData->id: null;
	}


    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
//        Log::debug('[beforeSave] Entity ID: ' . $entity->id);
//        Log::debug('[beforeSave] Incoming position_grades: ' . json_encode($entity->position_grades ?? null));
//        Log::debug('[beforeSave] position_grade_selection: ' . json_encode($entity->position_grade_selection ?? null));

        $grades = $entity->position_grades ?? [];

        // Normalize: extract IDs from entities or arrays
        $selectedGradeIds = array_map(function ($grade) {
            return is_array($grade) ? ($grade['id'] ?? null) : ($grade->id ?? null);
        }, $grades);

        $selectedGradeIds = array_filter($selectedGradeIds); // Remove nulls

//        Log::debug('[beforeSave] Extracted grade IDs: ' . json_encode($selectedGradeIds));

        $hasSelectAll = in_array(self::SELECT_ALL_POSITION_GRADES, $selectedGradeIds);
        $hasSomeRealGrades = !empty($selectedGradeIds) && !$hasSelectAll;

        $StaffPositionTitlesGrades = TableRegistry::getTableLocator()->get('Institution.StaffPositionTitlesGrades');

        // Remove -1 if some real grades are selected
        if ($hasSomeRealGrades  && !empty($entity->id)){
//            Log::debug('[beforeSave] Real grades selected — removing -1 if it exists');
            $StaffPositionTitlesGrades->deleteAll([
                'staff_position_title_id' => $entity->id,
                'staff_position_grade_id' => self::SELECT_ALL_POSITION_GRADES
            ]);
        }

        // Remove all real grades if SELECT_ALL is selected
        if ($hasSelectAll) {
//            Log::debug('[beforeSave] SELECT_ALL selected — removing all other grades for this title');
            $StaffPositionTitlesGrades->deleteAll([
                'staff_position_title_id' => $entity->id,
                'staff_position_grade_id !=' => self::SELECT_ALL_POSITION_GRADES
            ]);

            // Overwrite incoming to only keep -1 (if needed)
            $entity->position_grades = [['id' => self::SELECT_ALL_POSITION_GRADES]];
        }
    }


    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            case 'visible':
                return __('Visible');
            case 'name':
                return __('Name');
            case 'international_code':
                return __('International Code');
            case 'national_code':
                return __('National Code');
            case 'editable':
                return __('Editable');
            case 'default':
                return __('Default');
            case 'type':
                return __('Type');
            case 'staff_position_categories_id':
                return __('Staff Position Categories'); // POCOR-8128
            case 'security_role_id':
                return __('Security Role');
            case 'position_grade_selection':
                return __('Position Grade');
            case 'position_grades':
                return __('Position Grades');
            case 'file_content':
                return __('Attachment');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    // POCOR-8128 start
    private function getStaffLeavePolicyOptions(){
        $StaffLeavePolicyTable = self::getDynamicTableInstance('staff_leave_policies');
        $staffLeavePolicyOptions = $StaffLeavePolicyTable
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->toArray();
        return $staffLeavePolicyOptions;
    }

    private function getStaffPositionCategoryOptions(){
        $StaffPositionCategoryTable = self::getDynamicTableInstance('staff_position_categories');
        $staffPositionCategoryOptions = $StaffPositionCategoryTable
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->where(['visible' => 1])
            ->toArray();
        return $staffPositionCategoryOptions;
    }



    /**
     * POCOR-8391 added
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {

        }
        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }
        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }
    // POCOR-8128 end

}
