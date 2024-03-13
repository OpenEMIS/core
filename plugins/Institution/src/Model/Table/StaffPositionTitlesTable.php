<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Network\Session;
use Cake\Network\Request;
use Cake\Log\Log;
use ControllerAction\Model\Traits\UtilityTrait;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class StaffPositionTitlesTable extends ControllerActionTable
{
	use UtilityTrait;
	use OptionsTrait;

	CONST SELECT_POSITION_GRADES = 1;
	CONST SELECT_ALL_POSITION_GRADES = '-1';

	private $positionGradeSelection = [];

	public function initialize(array $config)
	{
        $this->table('staff_position_titles');
        parent::initialize($config);
        $this->hasMany('TrainingCoursesTargetPopulations', ['className' => 'Training.TrainingCoursesTargetPopulations', 'foreignKey' => 'target_population_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);
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

	public function validationDefault(Validator $validator)
	{
		$validator = parent::validationDefault($validator);
		return $validator
			->requirePresence('position_grades')
		    ->allowEmpty('file_content')//POCOR-7758
			->add('position_grades', 'ruleCheckPositionGrades', [
				'rule' => ['checkPositionGrades'],
				'provider' => 'table',
				'on' => function ($context) {  
				//trigger validation only when position grade selection is set to 1	 and edit operation
				return ($context['data']['position_grade_selection'] == self::SELECT_POSITION_GRADES  && !$context['newRecord']);
			}
		]);
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('type', [
			'visible' => true,
			'options' => $this->getSelectOptions('Staff.position_types'),
			'after' => 'name',
			'onChangeReload' => true
		]);
		$this->field('staff_position_categories_id', ['select' => false,'after' => 'type']); //POCOR-6950
		$extra['roleList'] = $this->SecurityRoles->getSystemRolesList();
		$this->field('security_role_id', ['after' => 'staff_position_categories_id', 'options' => $extra['roleList']]);
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra) {
		if ($this->Session->check('StaffPositionTitles.error')) {
			$this->Alert->error($this->Session->read('StaffPositionTitles.error'), ['reset' => true]);
			$this->Session->delete('StaffPositionTitles.error');
		}
		$this->field('type', ['after' => 'name']);
		$this->field('security_role_id', ['after' => 'type']);
		$this->field('file_content', ['after' => 'position_grades', 'attr' => ['label' => __('Description')], 'visible' => ['add' => true, 'view' => true, 'edit' => true, 'index' => false]]);//POCOR-7758
        $this->field('file_name',['visible'=>false]);//POCOR-7758
	}

	public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra) 
	{
		$entity->position_grade_selection = self::SELECT_POSITION_GRADES;
	}

	public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra) 
	{
		$this->field('file_name', ['visible' => false]);//POCOR-7758
		$this->field('file_content', ['attr' => ['label' => __('Description')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);//POCOR-7758
       
		$this->setupFields($entity);
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
	{
		if (array_key_exists($this->alias(), $requestData)) {
			if (isset($requestData[$this->alias()]['position_grades']['_ids']) && empty($requestData[$this->alias()]['position_grades']['_ids'])) {
				$requestData[$this->alias()]['position_grades'] = []; 
			}
		}
	}

	public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra) 
	{
		$isSelectAll = $this->checkIsSelectAll($entity);

		if ($isSelectAll) {
			$entity->position_grade_selection = self::SELECT_ALL_POSITION_GRADES;
		} else {
			$entity->position_grade_selection = self::SELECT_POSITION_GRADES;
		}
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) {

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

	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) 
	{
		$this->field('file_name', ['visible' => false]);//POCOR-7758
		$this->field('file_content', ['attr' => ['label' => __('Description')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);//POCOR-7758
		$this->setupFields($entity);
	}

	public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra) 
	{
		$query->contain(['PositionGrades']);
	}

	/**
     * Get all areas ids as key and name as value
     * @usage  It is used as drop-down options
     * @author Rahul Singh <rahul.singh@mail.valuecoders.com>
     * @ticket POCOR-6950
     */

	public function onUpdateFieldStaffPositionCategoriesId(Event $event, array $attr, $action, Request $request) 
	{
        if ($action == 'add' || $action == 'edit') {
        	list($levelOptions, $selectedLevel) = array_values($this->getTypeOptions($request, $action));//POCOR-7292 add param action
        	$attr['options'] = $levelOptions;
        	if ($action == 'add') {
        		$attr['default'] = $selectedLevel;
        	}else if($action == 'edit'){//POCOR-7292 starts
        		$typeId= $this->paramsDecode($request->params['pass'][1]);
        		$StaffPositionTitles = TableRegistry::get('staff_position_titles');
        		$Options = $StaffPositionTitles
					            ->find()
					            ->where([$StaffPositionTitles->aliasField('id') => $typeId['id']])
					            ->first();
				$attr['value'] = $levelOptions[$Options->staff_position_categories_id];
        	}//POCOR-7292 ends
        }
		return $attr;
	}

	/**
     * Get all areas ids as key and name as value
     * @usage  It is used as drop-down options
     * @author Rahul Singh <rahul.singh@mail.valuecoders.com>
     * @ticket POCOR-6950
     */

	public function getTypeOptions($request, $action = null)//POCOR-7292 add param $action
    {
		$type = $request->data['StaffPositionTitles']['type'];
		$StaffPositionCategories = TableRegistry::get('Staff.StaffPositionCategories');
        //POCOR-7292 starts
        if($action == 'edit'){
    		$StaffPositionTitlesPass= $this->paramsDecode($request->params['pass'][1]);
    		$StaffPositionTitles = TableRegistry::get('staff_position_titles');
    		$Options = $StaffPositionTitles
				            ->find()
				            ->where([$StaffPositionTitles->aliasField('id') => $StaffPositionTitlesPass['id']])
				            ->first();
			$type = !empty($Options) ? $Options->type : '';         
		}//POCOR-7292 ends
		$levelOptions = $StaffPositionCategories
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->where([ $StaffPositionCategories->aliasField('type') => $type ])
            ->toArray();
           
         $selectedLevel = !is_null($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);
         return compact('levelOptions', 'selectedLevel');
    }

	public function onUpdateFieldPositionGradeSelection(Event $event, array $attr, $action, Request $request) 
	{
		if ($action == 'add' || $action == 'edit') {
			$attr['options'] = $this->positionGradeSelection;
			$attr['select'] = false;
			$attr['onChangeReload'] = true;
		}
		return $attr;
	}

	public function onUpdateFieldPositionGrades(Event $event, array $attr, $action, Request $request) 
	{
		$requestData = $request->data;
		$entity = $attr['entity'];
		$staffPositionGradeOptions = TableRegistry::get('Institution.StaffPositionGrades')->getList()->toArray();

		$positionGradeSelection = null;
		if (isset($requestData[$this->alias()]['position_grade_selection'])) {
			$positionGradeSelection = $requestData[$this->alias()]['position_grade_selection'];
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
		$this->field('file_content', ['after' => 'position_grades', 'attr' => ['label' => __('Description')], 'visible' => ['add' => true, 'view' => true, 'edit' => true,'index'=>false]]);//POCOR-7758

	}

    public function onGetPositionGrades(Event $event, Entity $entity) 
    {
        $isSelectAll = $this->checkIsSelectAll($entity);

        if ($this->action == 'view' && $isSelectAll) {
            $StaffPositionTitles = TableRegistry::get('Institution.StaffPositionGrades');
            $list = $StaffPositionTitles
                ->find('list')
                ->find('order')
                ->toArray();

            return (!empty($list))? implode(', ', $list) : ' ';
        }
    }
    public function onGetType(Event $event, Entity $entity) 
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

	public function onGetStaffPositionCategoriesId(Event $event, Entity $entity) 
	{
		$StaffPositionCategories = TableRegistry::get('Staff.StaffPositionCategories');
            $list = $StaffPositionCategories
                ->find('list')
                ->find('order')
                ->where([ $StaffPositionCategories->aliasField('id') => $entity->staff_position_categories_id ])
                ->toArray();

            return (!empty($list))? implode(', ', $list) : ' ';
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) 
	{
		$this->setAllPositionGrades($entity);

		if (!$entity->isNew() && $entity->dirty('security_role_id')) {
			$oldRoleId = $entity->getOriginal('security_role_id');
			$newRoleId = $entity->security_role_id;
			$titleId = $entity->id;

			$this->startUpdateRoles($newRoleId, $titleId);
		}
	}

	private function setAllPositionGrades($entity) 
	{
		if ($entity->has('position_grade_selection') && $entity->position_grade_selection == self::SELECT_ALL_POSITION_GRADES) {
			$StaffPositionTitlesGrades = TableRegistry::get('Institution.StaffPositionTitlesGrades');
			$entityId = $entity->id;

			$data = [
				'staff_position_title_id' => $entityId,
				'staff_position_grade_id' => self::SELECT_ALL_POSITION_GRADES
			];

			$staffPositionTitlesGradesEntity = $StaffPositionTitlesGrades->newEntity($data);

			if ($StaffPositionTitlesGrades->save($staffPositionTitlesGradesEntity)) {
			} else {
				$StaffPositionTitlesGrades->log($staffPositionTitlesGradesEntity->errors(), 'debug');
			}
		}
	}

    public function checkIsSelectAll($entity) 
    {
        $StaffPositionTitlesGrades = TableRegistry::get('Institution.StaffPositionTitlesGrades');

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
		$SystemProcesses = TableRegistry::get('SystemProcesses');
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
		$SystemProcesses = TableRegistry::get('SystemProcesses');
		$runningProcess = $SystemProcesses->getErrorProcesses('Institution.StaffPositionTitles');
		foreach ($runningProcess as $process) {
			$param = json_decode($process['params']);
			if ($param->titleId == $titleId) {
				return $process;
			}
		}
		return false;
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Shell.shellRestartUpdateRole'] = 'shellRestartUpdateRole';
		return $events;
	}

	public function shellRestartUpdateRole(Event $event, $systemProcessId, $executedCount, $params) {
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
		$SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
		$InstitutionStaffTable = TableRegistry::get('Institution.Staff');

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
	public function getPrincipalRoleId()
    {
        $principalData = $this->find()
            ->select([$this->primaryKey()])
            ->where([$this->aliasField('name') => 'Principal'])
            ->first();

        return (!empty($principalData))? $principalData->id: null;
    }

	public function getDeputyPrincipalRoleId()
    {
        $deputyPrincipalData = $this->find()
            ->select([$this->primaryKey()])
            ->where([$this->aliasField('name') => 'Vice Principal'])
            ->first();

        return (!empty($deputyPrincipalData))? $deputyPrincipalData->id: null;
    }
}
