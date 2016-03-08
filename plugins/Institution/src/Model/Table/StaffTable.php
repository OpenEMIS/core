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
use Cake\ORM\ResultSet;
use Cake\I18n\Time;

use DateTime;

use Cake\Log\Log;

class StaffTable extends AppTable {
	use OptionsTrait;

	private $dashboardQuery = null;

	public function initialize(array $config) {
		$this->table('institution_staff');
		parent::initialize($config);

		$this->belongsTo('Users',			['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('Positions',		['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
		$this->belongsTo('Institutions',	['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('StaffTypes',		['className' => 'FieldOption.StaffTypes']);
		$this->belongsTo('StaffStatuses',	['className' => 'FieldOption.StaffStatuses']);
		$this->belongsTo('SecurityGroupUsers', ['className' => 'Security.SecurityGroupUsers']);

		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
		$this->addBehavior('AcademicPeriod.Period');
		// to handle field type (autocomplete)
		$this->addBehavior('OpenEmis.Autocomplete');
		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');
		$this->addBehavior('AcademicPeriod.AcademicPeriod');

		$this->addBehavior('Excel', [
			'excludes' => ['start_year', 'end_year', 'security_group_user_id'], 
			'pages' => ['index']
		]);

		$this->addBehavior('HighChart', [
	      	'number_of_staff' => [
        		'_function' => 'getNumberOfStaff',
				'chart' => ['type' => 'column', 'borderWidth' => 1],
				'xAxis' => ['title' => ['text' => __('Position Type')]],
				'yAxis' => ['title' => ['text' => __('Total')]]
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
			->allowEmpty('end_date')
			->add('end_date', 'ruleCompareDateReverse', [
		        'rule' => ['compareDateReverse', 'start_date', true]
	    	])
	    	->allowEmpty('staff_name')
			->add('staff_name', 'ruleInstitutionStaffId', [
				'rule' => ['institutionStaffId'],
				'on' => 'create'
			])
			->add('start_date', 'ruleStaffExistWithinPeriod', [
				'rule' => ['checkStaffExistWithinPeriod'],
				'on' => 'update'
			])
			->add('institution_position_id', 'ruleCheckFTE', [
				'rule' => ['checkFTE'],
			])
		;
	}

	public function validationAllowEmptyName(Validator $validator) {
		$validator = $this->validationDefault($validator);
        $validator->remove('staff_name');
        return $validator;
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$query->where([$this->aliasField('institution_id') => $institutionId]);
		$periodId = $this->request->query['academic_period_id'];
		if ($periodId > 0) {
			$query->find('academicPeriod', ['academic_period_id' => $periodId]);
		}
	}

	public function onExcelGetFTE(Event $event, Entity $entity) {
		return ($entity->FTE * 100) . '%';
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->fields['staff_id']['order'] = 5;
		$this->fields['institution_position_id']['order'] = 6;
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
		->where([$this->Positions->aliasField('institution_id') => $institutionId])
		->toArray();

		$positionOptions = [0 => __('All Positions')] + $positionData;

		// Query Strings
		$selectedPeriod = $this->queryString('academic_period_id', $periodOptions);
		$selectedPosition = $this->queryString('position', $positionOptions);

		$Staff = $this;

		// Advanced Select Options
		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage('general.noStaff'),
			'callable' => function($id) use ($Staff, $institutionId) {
				return $Staff
					->findByInstitutionId($institutionId)
					->find('academicPeriod', ['academic_period_id' => $id])
					->count();
			}
		]);
		$request->query['academic_period_id'] = $selectedPeriod;

		$this->advancedSelectOptions($positionOptions, $selectedPosition);

		$query->find('academicPeriod', ['academic_period_id' => $selectedPeriod]);
		if ($selectedPosition != 0) {
			$query->where([$this->aliasField('institution_position_id') => $selectedPosition]);
		}
		
		$search = $this->ControllerAction->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
		}

		$this->controller->set(compact('periodOptions', 'positionOptions'));
	}

	public function indexAfterPaginate(Event $event, ResultSet $resultSet) {
		$query = $resultSet->__debugInfo()['query'];
		$this->dashboardQuery = clone $query;
	}

	public function addStaffRole($staffEntity) {
		if (empty($staffEntity->security_group_user_id)) {
			// every staff record in school will be linked to a security role record in security_group_users
			$securityGroupId = $this->Institutions->get($staffEntity->institution_id)->security_group_id;
			$securityRoleId = $this->Positions->find()
				->where([
					$this->Positions->aliasField('id') => $staffEntity->institution_position_id
				])
				->matching('StaffPositionTitles.SecurityRoles')
				->select(['security_role_id' => 'SecurityRoles.id'])
				->first()
				->security_role_id;

			$SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
			$securityGroupUsersRecord = [
				'security_role_id' => $securityRoleId,
				'security_group_id' => $securityGroupId,
				'security_user_id' => $staffEntity->staff_id
			];

			$newSecurityGroupEntity = $SecurityGroupUsersTable->newEntity($securityGroupUsersRecord);
			$entity = $SecurityGroupUsersTable->save($newSecurityGroupEntity);
			$this->updateSecurityGroupUserId($staffEntity, $entity->id);
		}
	}

	public function removeStaffRole($staffEntity) {
		$SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
		$SecurityGroupUsersTable->deleteAll([$SecurityGroupUsersTable->primaryKey() => $staffEntity->security_group_user_id]);
		$this->updateSecurityGroupUserId($staffEntity, NULL);
	}

	public function addAfterAction(Event $event, Entity $entity) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$securityGroupId = $this->Institutions->get($institutionId)->security_group_id;
		$this->security_group_id = $securityGroupId;
		$this->ControllerAction->field('staff_name');
		$this->ControllerAction->field('institution_position_id');
		$this->ControllerAction->field('FTE');
		$this->ControllerAction->field('end_date', ['visible' => false]);
		$this->ControllerAction->field('staff_id', ['visible' => false]);
		$this->ControllerAction->field('group_id', ['type' => 'hidden', 'value' => $securityGroupId]);
		$this->ControllerAction->setFieldOrder([
			'institution_position_id', 'start_date', 'position_type', 'FTE', 'staff_type_id', 'staff_status_id', 'staff_name'
		]);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->Session->write('Staff.Staff.id', $entity->staff_id);
		$this->Session->write('Staff.Staff.name', $entity->user->name);
		$this->setupTabElements($entity);
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		if ($this->action == 'add') {
			$buttons[0]['name'] = '<i class="fa kd-add"></i> ' . __('Create New');
			$buttons[0]['attr']['value'] = 'new';
		}
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
					if (date('Y-m-d', strtotime($data[$alias]['start_date'])) < date('Y-m-d')) {
						$data[$alias]['end_date'] = date('Y-m-d');
					} else {
						$data[$alias]['end_date'] = date('Y-m-d', strtotime($data[$alias]['start_date']));
					}
				} else {
					$data[$alias]['end_date'] = date('Y-m-d', strtotime($newEndDate));
				}
			}
		}
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		$institutionPositionId = $entity->institution_position_id;
		$staffId = $entity->staff_id;
		$institutionId = $entity->institution_id;
		$securityGroupId = $this->Institutions->get($institutionId)->security_group_id;

		if (!$entity->isNew()) { // edit operation
			if ($entity->has('newFTE')) {
				unset($entity->id);
				$entity->FTE = $entity->newFTE;
				$entity->start_date = $entity->end_date;
				if ($entity->start_date instanceof Date) {
					$entity->start_date->modify('+1 days');
				} else {
					$startDate = $entity->start_date->format('Y-m-d');
					$date = date_create($startDate);
					date_add($date, date_interval_create_from_date_string('1 day'));
					$entity->start_date = $date->format('Y-m-d');
				}
				$entity->end_date = null;
				$entity->end_year = null;
				unset($entity->staff_type);
				unset($entity->staff_status);
				unset($entity->position);
				unset($entity->user);
				$newEntity = $this->newEntity($entity->toArray());
				if ($this->save($newEntity)) {
					$url = [
						'plugin' => 'Institution', 
						'controller' => 'Institutions', 
						'action' => 'Staff', 
						'0' => 'view', 
						'1' => $newEntity->id
					];
					$url = array_merge($url, $this->ControllerAction->params());
					$event->stopPropagation();
					return $this->controller->redirect($url);
				}
			} else {
				if (empty($entity->end_date) || $entity->end_date->isToday() || $entity->end_date->isFuture()) {
					$this->addStaffRole($entity);
				} else {
					$this->removeStaffRole($entity);
				}
			}
		} else { // add operation
			$this->addStaffRole($entity);
		}
	}

	private function updateSecurityGroupUserId($entity, $groupUserId) {
		$this->updateAll(
			['security_group_user_id' => $groupUserId],
			[$this->primaryKey() => $entity->id]
		);
	}

	private function setupTabElements($entity) {
		$options = [
			'userRole' => 'Staff',
			'action' => $this->action,
			'id' => $entity->id,
			'userId' => $entity->staff_id
		];
		$tabElements = TableRegistry::get('Staff.Staff')->getCareerTabElements($options);

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Positions');
	}

	public function onUpdateFieldInstitutionPositionId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$groupId = $this->security_group_id;
			$userId = $this->Auth->user('id');
			if ($this->AccessControl->isAdmin()) {
				$userId = null;
			}
			$positionTable = TableRegistry::get('Institution.InstitutionPositions');
			$activeStatusId = $this->Workflow->getStepsByModelCode($positionTable->registryAlias(), 'ACTIVE');
			$Roles = TableRegistry::get('Security.SecurityRoles');
			$roleOptions = $Roles->getPrivilegedRoleOptionsByGroup($groupId, $userId);
			$securityRoleIds = array_keys($roleOptions);
			$institutionId = $this->Session->read('Institution.Institutions.id');
	   		$types = $this->getSelectOptions('Staff.position_types');
			$positionOptions = new ArrayObject();
			$this->Positions
					->find()
					->matching('StaffPositionTitles.SecurityRoles')
					->where([$this->Positions->aliasField('institution_id') => $institutionId, $this->Positions->aliasField('status_id').' IN ' => $activeStatusId])
				    ->map(function ($row) use ($types, $positionOptions, $securityRoleIds) { // map() is a collection method, it executes the query
				        $type = array_key_exists($row->_matchingData['StaffPositionTitles']->type, $types) ? $types[$row->_matchingData['StaffPositionTitles']->type] : $row->_matchingData['StaffPositionTitles']->type;
				        if (in_array($row->_matchingData['SecurityRoles']->id, $securityRoleIds)) {
				        	$positionOptions[$type][$row->id] = $row->name;
				        }
				        return $row;
				    })
				    ->toArray(); // Also a collections library method
			$attr['options'] = $positionOptions->getArrayCopy();
		}
		return $attr;
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		if (isset($buttons['view'])) {
			$url = $this->ControllerAction->url('view');
			$url['action'] = 'StaffUser';
			$url[1] = $entity['_matchingData']['Users']['id'];
			$buttons['view']['url'] = $url;
		}

		if (isset($buttons['edit'])) {
			$url = $this->ControllerAction->url('edit');
			$url[1] = $entity->id;
			$buttons['edit']['url'] = $url;
		}

		return $buttons;
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
			$attr['target'] = ['key' => 'staff_id', 'name' => $this->aliasField('staff_id')];
			$attr['noResults'] = __('No Staff found');
			$attr['attr'] = ['placeholder' => __('OpenEMIS ID or Name')];
			$attr['url'] = ['controller' => 'Institutions', 'action' => 'Staff', 'ajaxUserAutocomplete'];
			
			$iconSave = '<i class="fa fa-check"></i> ' . __('Save');
			$iconAdd = '<i class="fa kd-add"></i> ' . __('Create New');
			$attr['onSelect'] = "$('.btn-save').html('" . $iconSave . "').val('save')";
			$attr['onNoResults'] = "$('.btn-save').html('" . $iconAdd . "').val('new')";
			$attr['onBeforeSearch'] = "$('.btn-save').html('" . $iconAdd . "').val('new')";
		} else if ($action == 'index') {
			$attr['sort'] = ['field' => 'Users.first_name'];
		}
		return $attr;
	}

	public function onGetStaffId(Event $event, Entity $entity) {
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
		$patch = $this->patchEntity($entity, $data->getArrayCopy(), $options->getArrayCopy());
		$errorCount = count($patch->errors());
		if ($errorCount == 0 || ($errorCount == 1 && array_key_exists('staff_id', $patch->errors()))) {
			$this->Session->write('Institution.Staff.new', $data[$this->alias()]);
			$event->stopPropagation();
			$action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'StaffUser', 'add'];
			return $this->controller->redirect($action);
		} else {
			$this->Alert->error('general.add.failed', ['reset' => true]);
		}
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->field('position_type');
		$this->ControllerAction->field('staff_type_id', ['type' => 'select', 'visible' => ['index' => false, 'view' => true, 'edit' => true]]);
		$this->ControllerAction->field('staff_status_id', ['type' => 'select']);
		$this->ControllerAction->field('staff_id');
		$this->ControllerAction->field('security_group_user_id', ['visible' => false]);
		
		if ($this->action == 'index') {
			$InstitutionArray = [];
			

			$session = $this->Session;
			$institutionId = $session->read('Institution.Institutions.id');

			$periodId = $this->request->query('academic_period_id');
			$conditions = ['institution_id' => $institutionId];

			$positionId = $this->request->query('position');

			$searchConditions = $this->getSearchConditions($this->Users, $this->request->data['Search']['searchField']);
			$searchConditions['OR'] = array_merge($searchConditions['OR'], $this->advanceNameSearch($this->Users, $this->request->data['Search']['searchField']));

			$institutionStaffQuery = clone $this->dashboardQuery;
			// Get Number of staff in an institution
			$staffCount = $institutionStaffQuery->count();

			unset($institutionStaffQuery);

			// Get Gender
			$InstitutionArray[__('Gender')] = $this->getDonutChart('institution_staff_gender', 
				['query' => $this->dashboardQuery, 'key' => __('Gender')]);

			// Get Staff Licenses
			$table = TableRegistry::get('Staff.Licenses');
			// Revisit here in awhile
			$InstitutionArray[__('Licenses')] = $table->getDonutChart('institution_staff_licenses', 
				['query' => $this->dashboardQuery, 'table'=>$this, 'key' => __('Licenses')]);

			$this->controller->viewVars['indexElements'][] = ['name' => 'Institution.Staff/controls', 'data' => [], 'options' => [], 'order' => 2];
			$indexDashboard = 'dashboard';
			$this->controller->viewVars['indexElements']['mini_dashboard'] = [
	            'name' => $indexDashboard,
	            'data' => [
	            	'model' => 'staff',
	            	'modelCount' => $staffCount,
	            	'modelArray' => $InstitutionArray,
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
		$this->fields['staff_id']['order'] = $i++;
		$this->fields['institution_position_id']['order'] = $i++;
		$this->fields['position_type']['order'] = $i++;
		$this->fields['FTE']['order'] = $i++;
	}

	public function editBeforeQuery(Event $event, Query $query) {
		$query->contain(['Users', 'Positions', 'StaffTypes', 'StaffStatuses']);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('staff_id', [
			'type' => 'readonly', 
			'order' => 10, 
			'attr' => ['value' => $entity->user->name_with_id]
		]);
		$this->ControllerAction->field('institution_position_id', [
			'type' => 'readonly', 
			'order' => 11,
			'attr' => ['value' => $entity->position->name]
		]);

		if (empty($entity->end_date)) {
			$this->ControllerAction->field('FTE', [
				'type' => 'select', 
				'order' => 12,
				'options' => ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1' => '100%']
			]);
		} else {
			$this->ControllerAction->field('FTE', [
				'type' => 'readonly', 
				'order' => 12, 
				'attr' => ['value' => $entity->FTE]
			]);
		}
		$this->Session->write('Staff.Staff.id', $entity->staff_id);
		$this->Session->write('Staff.Staff.name', $entity->user->name);
		$this->setupTabElements($entity);
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		// note that $this->table('institution_staff');
		$id = $entity->id;
		$institutionId = $entity->institution_id;
		$staffId = $entity->staff_id;

		
		$startDate = (!empty($entity->start_date))? $entity->start_date->format('Y-m-d'): null;
		$endDate = (!empty($entity->end_date))? $entity->end_date->format('Y-m-d'): null;
			
		$InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');

		// Deleting a staff-to-position record in a school removes all records related to the staff in the school (i.e. remove him from classes/subjects) falling between end date and start date of his assignment in the position.
		$classesInPosition = $InstitutionClasses->find()
			->where(
				['staff_id' => $staffId, 'institution_id' => $institutionId]
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
		$classArray = [];
		foreach ($classesInPosition as $key => $value) {
			$classArray[] = $value->id;
		}
		if (!empty($classArray)) {
			$InstitutionClasses->updateAll(
				['staff_id' => 0],
				['id IN ' => $classArray]
			);
		}
		// delete the staff from subjects		
		// find subjects that matched the start-end date then delete from subject_staff that matches staff id and subjects returned from previous 

		$InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');	
		$subjectsDuringStaffPeriod = $InstitutionSubjects->find()
			->where([$InstitutionSubjects->aliasField('institution_id') => $institutionId])
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
		$subjectIdsDuringStaffPeriod = [];
		foreach ($subjectsDuringStaffPeriod as $key => $value) {
			$subjectIdsDuringStaffPeriod[] = $value->id;
		}

		// Staff behavior associated to institution must be deleted.
		$StaffBehaviours = TableRegistry::get('Institution.StaffBehaviours');
		$StaffBehaviours->deleteAll([
			$StaffBehaviours->aliasField('staff_id') => $entity->staff_id,
			$StaffBehaviours->aliasField('institution_id') => $entity->institution_id,
		]);

		// Staff absence associated to institution must be deleted.
		$StaffAbsences = TableRegistry::get('Institution.StaffAbsences');
		$StaffAbsences->deleteAll([
			$StaffAbsences->aliasField('staff_id') => $entity->staff_id,
			$StaffAbsences->aliasField('institution_id') => $entity->institution_id,
		]);

		// Rubrics related to staff must be deleted. (institution_site_quality_rubrics)
		// association cascade deletes institution_site_quality_rubric_answers
		$InstitutionRubrics = TableRegistry::get('Institution.InstitutionRubrics');
		$institutionRubricsQuery = $InstitutionRubrics->find()
			->where([
				$InstitutionRubrics->aliasField('staff_id') => $entity->staff_id,
				$InstitutionRubrics->aliasField('institution_id') => $entity->institution_id,
			])
		;
		foreach ($institutionRubricsQuery as $key => $value) {
			$InstitutionRubrics->delete($value);
		}

		$InstitutionSubjectStaff = TableRegistry::get('Institution.InstitutionSubjectStaff');

		$InstitutionSubjectStaff->deleteAll([
			$InstitutionSubjectStaff->aliasField('staff_id') => $staffId,
			$InstitutionSubjectStaff->aliasField('institution_subject_id') . ' IN ' => $subjectIdsDuringStaffPeriod
		]);

		// this logic here is to delete the roles from groups when the staff is deleted from the school
		try {
			
			$this->removeStaffRole($entity);

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
		$query = $params['query'];
		$InstitutionRecords = clone $query;
		$InstitutionStaffCount = $InstitutionRecords
			->matching('Users.Genders')
			->select([
				'count' => $InstitutionRecords->func()->count('DISTINCT staff_id'),	
				'gender' => 'Genders.name'
			])
			->group('gender_id');

		// Creating the data set		
		$dataSet = [];
		foreach ($InstitutionStaffCount->toArray() as $value) {
            //Compile the dataset
			$dataSet[] = [__($value['gender']), $value['count']];
		}
		$params['dataSet'] = $dataSet;
		
		unset($InstitutionRecords);

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
			->contain(['Users.Genders','Positions.StaffPositionTitles'])
			->select([
				'Positions.id',
				'StaffPositionTitles.type',
				'Users.id',
				'Genders.name',
				'total' => $query->func()->count('DISTINCT '.$this->aliasField('staff_id'))
			])
			->where($staffsByPositionConditions)
			->group([
				'StaffPositionTitles.type', 'Genders.name'
			])
			->order(
				'StaffPositionTitles.type'
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
				$positionType = $staffByPosition->position->staff_position_title->type;
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

		$params['options']['subtitle'] = array('text' => sprintf(__('For Year %s'), $currentYear));
		$params['options']['xAxis']['categories'] = array_values($positionTypes);
		$params['dataSet'] = $dataSet;

		return $params;
	}

// Functions that are migrated over
/******************************************************************************************************************
**
** finders functions to be used with query
**
******************************************************************************************************************/
	/**
	 * $options['type'] == 0 > non-teaching
	 * $options['type'] == 1 > teaching
	 * refer to OptionsTrait
	 */
	public function findByPositions(Query $query, array $options) {
		if (array_key_exists('Institutions.id', $options) && array_key_exists('type', $options)) {
			$StaffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');
			$positions = $this->Positions->find('list')
						->find('withBelongsTo')
				        ->where([
				        	'Institutions.id' => $options['Institutions.id'],
				        	$StaffPositionTitles->aliasField('type') => $options['type']
				        ])
				        ->toArray()
				        ;
			$positions = array_keys($positions);
			return $query->where([$this->aliasField('institution_position_id IN') => $positions]);
		} else {
			return $query;
		}
	}

	public function findByInstitution(Query $query, array $options) {
		if (array_key_exists('Institutions.id', $options)) {
			return $query->where([$this->aliasField('institution_id') => $options['Institutions.id']]);
		} else {
			return $query;
		}
	}

	/**
	 * currently available values:
	 * 	Full-Time
	 * 	Part-Time
	 * 	Contract
	 */
	public function findByType(Query $query, array $options) {
		if (array_key_exists('type', $options)) {
			$types = $this->StaffTypes->getList()->toArray();
			if (is_array($types) && in_array($options['type'], $types)) {
				$typeId = array_search($options['type'], $types);
				return $query->where([$this->aliasField('staff_type_id') => $typeId]);
			} else {
				return $query;
			}
		} else {
			return $query;
		}
	}

	/**
	 * currently available values:
	 * 	Current
	 * 	Transferred
	 * 	Resigned
	 * 	Leave
	 * 	Terminated
	 */
	public function findByStatus(Query $query, array $options) {
		if (array_key_exists('status', $options)) {
			$statuses = $this->StaffStatuses->getList()->toArray();
			if (is_array($statuses) && in_array($options['status'], $statuses)) {
				$statusId = array_search($options['status'], $statuses);
				return $query->where([$this->aliasField('staff_status_id') => $statusId]);
			} else {
				return $query;
			}
		} else {
			return $query;
		}
	}

	public function findWithBelongsTo(Query $query, array $options) {
		return $query
			->contain(['Users', 'Institutions', 'Positions', 'StaffTypes', 'StaffStatuses']);
	}

	public function removeInactiveStaffSecurityRole() {
		$SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');

		$StaffTable = $this;
		while(true) {
			$query = $this->find()
				->where([
					$this->aliasField('security_group_user_id IS NOT NULL'),
					$this->aliasField('end_date IS NOT NULL')
				])
				->where(
					function ($exp) use ($StaffTable) {
						return $exp->lt($StaffTable->aliasField('end_date'), $StaffTable->find()->func()->now('date'));
					}
				)
				->limit(10)
				->page(1)
				;

			$resultSet = $query->all();

			if ($resultSet->count() == 0) {
				break;
			} else {
				foreach ($resultSet as $entity) {
					Log::write('debug', __FUNCTION__ . ' - Removing roles for user_id (' . $entity->staff_id . ')');
					$SecurityGroupUsers->deleteAll([
						$SecurityGroupUsers->aliasField($SecurityGroupUsers->primaryKey()) => $entity->security_group_user_id
					]);
					$this->updateAll(
						['security_group_user_id' => NULL],
						[$this->primaryKey() => $entity->id]
					);
				}
			}
		}
	}

	public function removeIndividualStaffSecurityRole($staffId) {
		$SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
		$StaffTable = $this;
		$institutionStaffRecords = $this->find()
			->where([
				$this->aliasField('security_group_user_id IS NOT NULL'),
				$this->aliasField('end_date IS NOT NULL'),
				$this->aliasField('staff_id') => $staffId
			])
			->where(
				function ($exp) use ($StaffTable) {
					return $exp->lt($StaffTable->aliasField('end_date'), $StaffTable->find()->func()->now('date'));
				}
			)
			->toArray();
		foreach($institutionStaffRecords as $entity) {
			$SecurityGroupUsers->deleteAll([
				$SecurityGroupUsers->aliasField($SecurityGroupUsers->primaryKey()) => $entity->security_group_user_id
			]);
			$this->updateAll(
				['security_group_user_id' => NULL],
				[$this->primaryKey() => $entity->id]
			);
		}
	}
}
