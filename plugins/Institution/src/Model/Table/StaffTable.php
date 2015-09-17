<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Controller\Controller;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Utility\Inflector;


class StaffTable extends AppTable {
	use OptionsTrait;

	public function initialize(array $config) {
		$this->table('institution_site_staff');
		parent::initialize($config);

		$this->belongsTo('Users',			['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Positions',		['className' => 'Institution.InstitutionSitePositions', 'foreignKey' => 'institution_site_position_id']);
		$this->belongsTo('Institutions',	['className' => 'Institution.InstitutionSites', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('StaffTypes',		['className' => 'FieldOption.StaffTypes']);
		$this->belongsTo('StaffStatuses',	['className' => 'FieldOption.StaffStatuses']);

		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
		$this->addBehavior('AcademicPeriod.Period');
		// to handle field type (autocomplete)
		$this->addBehavior('OpenEmis.Autocomplete');
		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');
		$this->addBehavior('AcademicPeriod.AcademicPeriod');

		$this->addBehavior('Excel', [
			'excludes' => ['start_year', 'end_year'], 
			'pages' => ['index']
		]);

		$this->addBehavior('HighChart', [
	      	'number_of_staff' => [
        		'_function' => 'getNumberOfStaff',
				'chart' => ['type' => 'column', 'borderWidth' => 1],
				'xAxis' => ['title' => ['text' => 'Position Type']],
				'yAxis' => ['title' => ['text' => 'Total']]
			],
			'institution_staff_gender' => [
				'_function' => 'getNumberOfStaffsByGender'
			],
			'institution_staff_qualification' => [
				'_function' => 'getNumberOfStaffsByQualification'
			],
		]);
	}

	public function validationDefault(Validator $validator) {
		return $validator
			->add('institution_site_position_id', [
			])
			->add('institution_site_id', [
			])
			->add('staff_name', 'ruleInstitutionStaffId', [
				'rule' => ['institutionStaffId'],
				'on' => 'create'
			])
			->add('FTE', 'ruleCheckFTE', [
				'rule' => ['checkFTE'],
			])
		;
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$query->where([$this->aliasField('institution_site_id') => $institutionId]);
		$periodId = $this->request->query['academic_period_id'];
		if ($periodId > 0) {
			$query->find('academicPeriod', ['academic_period_id' => $periodId]);
		}
	}

	public function onExcelGetFTE(Event $event, Entity $entity) {
		return ($entity->FTE * 100) . '%';
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->fields['security_user_id']['order'] = 5;
		$this->fields['institution_site_position_id']['order'] = 6;
		$this->fields['FTE']['visible'] = false;
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->contain(['Positions']);
		$sortList = ['start_date', 'end_date'];
		if (array_key_exists('sortWhitelist', $options)) {
			$sortList = array_merge($options['sortWhitelist'], $sortList);
		}
		$options['sortWhitelist'] = $sortList;

		$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		// Academic Periods
		$periodOptions = $AcademicPeriodTable->getList();

		if (empty($request->query['academic_period_id'])) {
			$request->query['academic_period_id'] = $AcademicPeriodTable->getCurrent();
		}

		// Positions
		$session = $request->session();
		$institutionId = $session->read('Institution.Institutions.id');
		$positionData = $this->Positions
		->find('list', ['keyField' => 'id', 'valueField' => 'name'])
		->contain(['StaffPositionTitles'])
		->where([$this->Positions->aliasField('institution_site_id') => $institutionId])
		->toArray();

		$positionOptions = [0 => __('All Positions')] + $positionData;

		// Query Strings
		$selectedPeriod = $this->queryString('academic_period_id', $periodOptions);
		$selectedPosition = $this->queryString('position', $positionOptions);

		// Advanced Select Options
		$this->advancedSelectOptions($periodOptions, $selectedPeriod);
		$this->advancedSelectOptions($positionOptions, $selectedPosition);

		$query->find('academicPeriod', ['academic_period_id' => $selectedPeriod]);
		if ($selectedPosition != 0) {
			$query->where([$this->aliasField('institution_site_position_id') => $selectedPosition]);
		}
		
		$search = $this->ControllerAction->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
		}

		$this->controller->set(compact('periodOptions', 'positionOptions'));
	}

	public function addAfterSave(Event $event, Entity $entity, ArrayObject $data) {
		if ($entity->role > 0) {
			$obj = [
				'id' => Text::uuid(),
				'security_group_id' => $entity->group_id, 
				'security_role_id' => $entity->role, 
				'security_user_id' => $entity->security_user_id
			];
			$GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
			$GroupUsers->save($GroupUsers->newEntity($obj));
		}
	}

	public function addAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('staff_name');
		$this->ControllerAction->field('institution_site_position_id');
		$this->ControllerAction->field('role');
		$this->ControllerAction->field('FTE');
		$this->ControllerAction->field('end_date', ['visible' => false]);

		$this->ControllerAction->setFieldOrder([
			'institution_site_position_id', 'role', 'start_date', 'position_type', 'FTE', 'staff_type_id', 'staff_status_id', 'staff_name'
		]);

		$this->setupTabElements($entity);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupTabElements($entity);
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$alias = $this->alias();
		if (array_key_exists('FTE', $data[$alias])) {
			$newFTE = $data[$alias]['FTE'];
			$newEndDate = $data[$alias]['end_date'];

			if ($newFTE != $entity->FTE) {
				$data[$alias]['FTE'] = $entity->FTE;
				$entity->newFTE = $newFTE;

				if (empty($newEndDate)) {
					$data[$alias]['end_date'] = date('Y-m-d');
				}
			}
		}
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		if (!$entity->isNew()) { // edit operation
			if ($entity->has('newFTE')) {
				unset($entity->id);
				$entity->FTE = $entity->newFTE;
				$entity->start_date = $entity->end_date;
				if ($entity->start_date instanceof Date) {
					$entity->start_date->modify('+1 days');
				} else {
					$entity->start_date = date('Y-m-d', strtotime($entity->start_date . ' +1 day'));
				}
				$entity->end_date = null;
				$entity->end_year = null;
				unset($entity->staff_type);
				unset($entity->staff_status);
				unset($entity->position);
				unset($entity->user);
				
				$newEntity = $this->newEntity($entity->toArray());
				if (!$this->save($newEntity)) {
					pr($newEntity->errors());die;
				}
			}
		}
	}

	public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions) {
		
	}

	private function setupTabElements($entity) {
		$options = [
			'userRole' => 'Staff',
			'action' => $this->action,
			'id' => $entity->id,
			'userId' => $entity->security_user_id
		];

		$tabElements = $this->controller->getUserTabElements($options);

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function onUpdateFieldInstitutionSitePositionId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$institutionId = $this->Session->read('Institution.Institutions.id');
			$positionOptions = $this->Positions
			->find('list', ['keyField' => 'id', 'valueField' => 'name'])
			->contain(['StaffPositionTitles'])
			->where([$this->Positions->aliasField('institution_site_id') => $institutionId])
			->toArray();
			$attr['options'] = $positionOptions;
		}
		return $attr;
	}

	public function onUpdateFieldRole(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$Roles = TableRegistry::get('Security.SecurityRoles');
			$institutionId = $this->Session->read('Institution.Institutions.id');
			$institutionEntity = $this->Institutions->get($institutionId);
			$groupId = $institutionEntity->security_group_id;
			$this->ControllerAction->field('group_id', ['type' => 'hidden', 'value' => $groupId]);

			$userId = $this->Auth->user('id');
			if ($this->AccessControl->isAdmin()) {
				$userId = null;
			}
			$roleOptions = [0 => '-- ' . __('Select Role') . ' --'];
			$roleOptions = $roleOptions + $Roles->getPrivilegedRoleOptionsByGroup($groupId, $userId);
			$attr['options'] = $roleOptions;
		}
		return $attr;
	}

	public function onUpdateFieldPositionType(Event $event, array $attr, $action, Request $request) {
		$options = $this->getSelectOptions('Position.types');
		if ($action == 'add') {
			$attr['options'] = $options;
			$attr['onChangeReload'] = true;
			$this->fields['FTE']['type'] = 'hidden';
			$this->fields['FTE']['value'] = 1;
			if ($this->request->is(['post', 'put'])) {
				$type = $this->request->data($this->aliasField('position_type'));
				if ($type == 'PART_TIME') {
					$this->fields['FTE']['type'] = 'select';
					$this->fields['FTE']['options'] = [
						['value' => '0.25', 'text' => '25%'],
						['value' => '0.50', 'text' => '50%', 'selected'],
						['value' => '0.75', 'text' => '75%']
					];
				}
			}
		} else if ($action == 'view') {
			$this->fields['FTE']['type'] = 'string';
		} else {
			$attr['visible'] = false;
		}
		return $attr;
	}

	public function onUpdateFieldSecurityUserId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['visible'] = false;	
		} else if ($action == 'index') {
			$attr['sort'] = ['field' => 'Users.first_name'];
		}
		return $attr;
	}

	public function onUpdateFieldStaffName(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['type'] = 'autocomplete';
			$attr['target'] = ['key' => 'security_user_id', 'name' => $this->aliasField('security_user_id')];
			$attr['noResults'] = __('No Staff found');
			$attr['attr'] = ['placeholder' => __('OpenEMIS ID or Name')];
			$attr['url'] = ['controller' => 'Institutions', 'action' => 'Staff', 'ajaxUserAutocomplete'];
			
			$iconSave = '<i class="fa fa-check"></i> ' . __('Save');
			$iconAdd = '<i class="fa kd-add"></i> ' . __('Create New');
			$attr['onNoResults'] = "$('.btn-save').html('" . $iconAdd . "').val('new')";
			$attr['onBeforeSearch'] = "$('.btn-save').html('" . $iconSave . "').val('save')";
		} else if ($action == 'index') {
			$attr['sort'] = ['field' => 'Users.first_name'];
		}
		return $attr;
	}

	public function onGetSecurityUserId(Event $event, Entity $entity) {
		$value = '';
		if ($entity->has('user')) {
			$value = $entity->user->name;
		} else {
			$value = $entity->_matchingData['Users']->name;
		}
		return $value;
	}

	public function onGetPositionType(Event $event, Entity $entity) {
		$options = $this->getSelectOptions('Position.types');
		$value = $options['FULL_TIME'];
		if ($entity->FTE < 1) {
			$value = $options['PART_TIME'];
		}
		return $value;
	}

	public function onGetFTE(Event $event, Entity $entity) {
		$value = '100%';
		if ($entity->FTE < 1) {
			$value = ($entity->FTE * 100) . '%';
		}
		return $value;
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		$this->Session->delete('Institution.Staff.new');
	}

	public function addOnNew(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$this->Session->write('Institution.Staff.new', $data[$this->alias()]);
		$event->stopPropagation();
		$action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'StaffUser', 'add'];
		return $this->controller->redirect($action);
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->field('position_type');
		$this->ControllerAction->field('staff_type_id', ['type' => 'select', 'visible' => ['index' => false, 'view' => true, 'edit' => true]]);
		$this->ControllerAction->field('staff_status_id', ['type' => 'select']);
		$this->ControllerAction->field('security_user_id');
		
		if ($this->action == 'index') {
			$institutionSiteArray = [];
			

			$session = $this->Session;
			$institutionId = $session->read('Institution.Institutions.id');

			$periodId = $this->request->query('academic_period_id');
			$conditions = ['institution_site_id' => $institutionId];

			$positionId = $this->request->query('position');

			// Get Number of staff in an institution
			$staffCount = $this->find()
				->find('academicPeriod', ['academic_period_id' => $periodId])
				->where([$this->aliasField('institution_site_id') => $institutionId])
				->distinct(['security_user_id']);

			if ($positionId != 0) {
				$staffCount->where([$this->aliasField('institution_site_position_id') => $positionId]);
				$conditions = array_merge($conditions, ['institution_site_position_id' => $positionId])	;
			}
			// Get Gender
			$institutionSiteArray[__('Gender')] = $this->getDonutChart('institution_staff_gender', 
				['conditions' => $conditions, 'key' => __('Gender')]);

			// Get Staff Licenses
			$table = TableRegistry::get('Staff.Licenses');
			// Revisit here in awhile
			$institutionSiteArray[__('Licenses')] = $table->getDonutChart('institution_staff_licenses', 
				['conditions' => $conditions, 'key' => __('Licenses')]);

			$this->controller->viewVars['indexElements'][] = ['name' => 'Institution.Staff/controls', 'data' => [], 'options' => [], 'order' => 2];
			$indexDashboard = 'dashboard';
			$this->controller->viewVars['indexElements']['mini_dashboard'] = [
	            'name' => $indexDashboard,
	            'data' => [
	            	'model' => 'staff',
	            	'modelCount' => $staffCount->count(['security_user_id']),
	            	'modelArray' => $institutionSiteArray,
	            ],
	            'options' => [],
	            'order' => 1
	        ];
		}
	}

	public function viewBeforeAction(Event $event) {
		$this->ControllerAction->field('photo_content', ['type' => 'image', 'order' => 0]);
		$this->ControllerAction->field('openemis_no', ['type' => 'readonly', 'order' => 1]);
		$i = 10;
		$this->fields['security_user_id']['order'] = $i++;
		$this->fields['institution_site_position_id']['order'] = $i++;
		$this->fields['position_type']['order'] = $i++;
		$this->fields['FTE']['order'] = $i++;
	}

	public function editBeforeQuery(Event $event, Query $query) {
		$query->contain(['Users', 'Positions', 'StaffTypes', 'StaffStatuses']);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('security_user_id', [
			'type' => 'readonly', 
			'order' => 10, 
			'attr' => ['value' => $entity->user->name_with_id]
		]);
		$this->ControllerAction->field('institution_site_position_id', [
			'type' => 'readonly', 
			'order' => 11,
			'attr' => ['value' => $entity->position->name]
		]);
		$this->ControllerAction->field('FTE', [
			'type' => 'select', 
			'order' => 12,
			'options' => ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1' => '100%']
		]);
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		// note that $this->table('institution_site_staff');
		$id = $entity->id;
		$institutionId = $entity->institution_site_id;
		$securityUserId = $entity->security_user_id;

		
		$startDate = (!empty($entity->start_date))? $entity->start_date->format('Y-m-d'): null;
		$endDate = (!empty($entity->end_date))? $entity->end_date->format('Y-m-d'): null;
			

		$InstitutionSiteSections = TableRegistry::get('Institution.InstitutionSiteSections');
		// Deleting a staff-to-position record in a school removes all records related to the staff in the school (i.e. remove him from classes/subjects) falling between end date and start date of his assignment in the position.
		$sectionsInPosition = $InstitutionSiteSections->find()
			->where(
				['security_user_id' => $securityUserId, 'institution_site_id' => $institutionId]
			)
			->matching('AcademicPeriods', function ($q) use ($startDate, $endDate) {
				$overlapDateCondition = [];
				if (empty($endDate)) {
					$overlapDateCondition['AcademicPeriods.end_date' . ' >= '] = $startDate;
				} else {
					$overlapDateCondition['OR'] = [];
					$overlapDateCondition['OR'][] = ['AcademicPeriods.start_date' . ' >= ' => $startDate, 'AcademicPeriods.start_date' . ' <= ' => $endDate];
					$overlapDateCondition['OR'][] = ['AcademicPeriods.end_date' . ' >= ' => $startDate, 'AcademicPeriods.end_date' . ' <= ' => $endDate];
					$overlapDateCondition['OR'][] = ['AcademicPeriods.start_date' . ' <= ' => $startDate, 'AcademicPeriods.end_date' . ' >= ' => $endDate];
				}
				return $q->where($overlapDateCondition);
			})
			;
			
		$sectionArray = [];
		foreach ($sectionsInPosition as $key => $value) {
			$sectionArray[] = $value->id;
		}
		if (!empty($sectionArray)) {
			$InstitutionSiteSections->updateAll(
				['security_user_id' => 0],
				['id IN ' => $sectionArray]
			);
		}

		// delete the staff from subjects		
		// find classes that matched the start-end date then delete from class_staff that matches staff id and classes returned from previous 
		$InstitutionSiteClasses = TableRegistry::get('Institution.InstitutionSiteClasses');	
		$classesDuringStaffPeriod = $InstitutionSiteClasses->find()
			->matching('AcademicPeriods', function ($q) use ($startDate, $endDate) {
				$overlapDateCondition = [];
				if (empty($endDate)) {
					$overlapDateCondition['AcademicPeriods.end_date' . ' >= '] = $startDate;
				} else {
					$overlapDateCondition['OR'] = [];
					$overlapDateCondition['OR'][] = ['AcademicPeriods.start_date' . ' >= ' => $startDate, 'AcademicPeriods.start_date' . ' <= ' => $endDate];
					$overlapDateCondition['OR'][] = ['AcademicPeriods.end_date' . ' >= ' => $startDate, 'AcademicPeriods.end_date' . ' <= ' => $endDate];
					$overlapDateCondition['OR'][] = ['AcademicPeriods.start_date' . ' <= ' => $startDate, 'AcademicPeriods.end_date' . ' >= ' => $endDate];
				}
				return $q->where($overlapDateCondition);
			})
			;
		$classIdsDuringStaffPeriod = [];
		foreach ($classesDuringStaffPeriod as $key => $value) {
			$classIdsDuringStaffPeriod[] = $value->id;
		}

		$InstitutionSiteClassStaff = TableRegistry::get('Institution.InstitutionSiteClassStaff');

		$targetData = $InstitutionSiteClassStaff->find()
			->where([$InstitutionSiteClassStaff->aliasField('security_user_id') => $securityUserId,
			$InstitutionSiteClassStaff->aliasField('institution_site_class_id') . ' IN ' => $classIdsDuringStaffPeriod])
			;

		$InstitutionSiteClassStaff->deleteAll([
			$InstitutionSiteClassStaff->aliasField('security_user_id') => $securityUserId,
			$InstitutionSiteClassStaff->aliasField('institution_site_class_id') . ' IN ' => $classIdsDuringStaffPeriod
		]);


		// If the staff changes his FTE in a position, a new record for the same position needs to be created. The end date of the previous position record is automatically set to the start date of the new position record.





		// this will be a problem as staff with more than one position will get all their roles deleted from groups
		// solution is to link position to roles so only roles linked to that position will be deleted

		// this logic here is to delete the roles from groups when the staff is deleted from the school
		try {
			$institutionEntity = $this->Institutions->get($institutionId);
			$groupId = $institutionEntity->security_group_id;
			$GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
			$GroupUsers->deleteAll([
				'security_user_id' => $entity->security_user_id,
				'security_group_id' => $groupId
			]);
		} catch (InvalidPrimaryKeyException $ex) {
			Log::write('error', __METHOD__ . ': ' . $this->Institutions->alias() . ' primary key not found (' . $institutionId . ')');
		}
	}

	public function ajaxUserAutocomplete() {
		$this->controller->autoRender = false;
		$this->ControllerAction->autoRender = false;

		if ($this->request->is(['ajax'])) {
			$term = $this->request->query['term'];
			// only search for staff
			$query = $this->Users->find()->where([$this->Users->aliasField('is_staff') => 1]);

			$term = trim($term);
			if (!empty($term)) {
				$query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $term]);
			}
			
			$list = $query->all();

			$data = [];
			foreach($list as $obj) {
				$label = sprintf('%s - %s', $obj->openemis_no, $obj->name);
				$data[] = ['label' => $label, 'value' => $obj->id];
			}

			echo json_encode($data);
			die;
		}
	}

	// Function used by the Mini-Dashboard (Institution Staff)
	public function getNumberOfStaffsByGender($params=[]) {
			$conditions = isset($params['conditions']) ? $params['conditions'] : [];
			$_conditions = [];
			foreach ($conditions as $key => $value) {
				$_conditions[$this->alias().'.'.$key] = $value;
			}

			$institutionSiteRecords = $this->find();
			$institutionSiteStaffCount = $institutionSiteRecords
				->contain(['Users', 'Users.Genders'])
				->select([
					'count' => $institutionSiteRecords->func()->count('DISTINCT security_user_id'),	
					'gender' => 'Genders.name'
				])
				->where($_conditions)
				->group('gender_id');

			// Creating the data set		
			$dataSet = [];
			foreach ($institutionSiteStaffCount->toArray() as $value) {
	            //Compile the dataset
				$dataSet[] = [__($value['gender']), $value['count']];
			}
			$params['dataSet'] = $dataSet;
		//}
		return $params;
	}

	// Function used by the Dashboard (For Institution Dashboard and Home Page)
	public function getNumberOfStaff($params=[]) {
		$conditions = isset($params['conditions']) ? $params['conditions'] : [];
		$_conditions = [];
		foreach ($conditions as $key => $value) {
			$_conditions[$this->alias().'.'.$key] = $value;
		}

		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$currentYearId = $AcademicPeriod->getCurrent();
		$currentYear = $AcademicPeriod->get($currentYearId, ['fields'=>'name'])->name;

		$staffsByPositionConditions = ['Genders.name IS NOT NULL'];
		$staffsByPositionConditions = array_merge($staffsByPositionConditions, $_conditions);

		$query = $this->find('all');
		$staffByPositions = $query
			->find('AcademicPeriod', ['academic_period_id'=> $currentYearId])
			->contain(['Users.Genders','Positions'])
			->select([
				'Positions.type',
				'Users.id',
				'Genders.name',
				'total' => $query->func()->count('DISTINCT '.$this->aliasField('security_user_id'))
			])
			->where($staffsByPositionConditions)
			->group([
				'Positions.type', 'Genders.name'
			])
			->order(
				'Positions.type'
			)
			->toArray();

		$positionTypes = array(
			0 => __('Non-Teaching'),
			1 => __('Teaching')
		);

		$genderOptions = $this->Users->Genders->getList();
		$dataSet = array();
		foreach ($genderOptions as $key => $value) {
			$dataSet[$value] = array('name' => __($value), 'data' => []);
		}
		foreach ($dataSet as $key => $obj) {
			foreach ($positionTypes as $id => $name) {
				$dataSet[$key]['data'][$id] = 0;
			}
		}
		foreach ($staffByPositions as $key => $staffByPosition) {
			if ($staffByPosition->has('position')) {
				$positionType = $staffByPosition->position->type;
				$staffGender = $staffByPosition->user->gender->name;
				$StaffTotal = $staffByPosition->total;

				foreach ($dataSet as $dkey => $dvalue) {
					if (!array_key_exists($positionType, $dataSet[$dkey]['data'])) {
						$dataSet[$dkey]['data'][$positionType] = 0;
					}
				}
				$dataSet[$staffGender]['data'][$positionType] = $StaffTotal;
			}
		}

		$params['options']['subtitle'] = array('text' => 'For Year '. $currentYear);
		$params['options']['xAxis']['categories'] = array_values($positionTypes);
		$params['dataSet'] = $dataSet;

		return $params;
	}
}
