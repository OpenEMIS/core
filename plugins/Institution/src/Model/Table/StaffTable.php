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
use Security\Model\Table\SecurityUserTypesTable as UserTypes;

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
		$this->addBehavior('OpenEmis.autocomplete');
		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');

		// $this->addBehavior('Student.Student');
		// $this->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' =>['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		// $this->addBehavior('Institution.User', ['associatedModel' => $this->InstitutionSiteStudents]);
		// $this->addBehavior('AdvanceSearch');
	}

	public function beforeAction(Event $event) {
		// $institutionId = $this->Session->read('Institutions.id');
		// $this->ControllerAction->field('institution_id', ['type' => 'hidden', 'value' => $institutionId]);
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
		// Student Statuses
		// $statusOptions = $this->StudentStatuses
		// 	->find('list')
		// 	->toArray();

		// Academic Periods
		$periodOptions = TableRegistry::get('AcademicPeriod.AcademicPeriods')->getList();

		// Positions
		$session = $request->session();
		$institutionId = $session->read('Institutions.id');
		$positionData = $this->Positions
		->find()
		->contain(['StaffPositionTitles'])
		->where([$this->Positions->aliasField('institution_site_id') => $institutionId])
		->all();

		$positionOptions = [0 => __('All Positions')];
		foreach ($positionData as $position) {
			$positionOptions[$position->id] = $position->name;
		}

		// // Query Strings
		// $selectedStatus = $this->queryString('status_id', $statusOptions);
		$selectedPeriod = $this->queryString('period', $periodOptions);
		$selectedPosition = $this->queryString('position', $positionOptions);

		$query->find('academicPeriod', ['academic_period_id' => $selectedPeriod]);
		if ($selectedPosition != 0) {
			$query->where([$this->aliasField('institution_site_position_id') => $selectedPosition]);
		}

		// // Advanced Select Options
		// $this->advancedSelectOptions($statusOptions, $selectedStatus);
		$this->advancedSelectOptions($periodOptions, $selectedPeriod);
		$this->advancedSelectOptions($positionOptions, $selectedPosition);

		// $query->where([
		// 	$this->aliasField('student_status_id') => $selectedStatus,
		// 	$this->aliasField('academic_period_id') => $selectedAcademicPeriod
		// ]);

		$search = $this->ControllerAction->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->addSearchConditions($query, ['searchTerm' => $search]);
		}

		$this->controller->set(compact('statusOptions', 'periodOptions', 'positionOptions'));
		// End
	}

	public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$errors = $entity->errors();
		if (!empty($errors)) {
			$entity->unsetProperty('security_user_id');
			unset($data[$this->alias()]['security_user_id']);
		}
	}

	public function addAfterSave(Event $event, Controller $controller, Entity $entity) {
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

	public function addAfterAction(Event $event) {
		$this->ControllerAction->field('institution_site_position_id');
		$this->ControllerAction->field('role');
		$this->ControllerAction->field('FTE');
		$this->ControllerAction->field('end_date', ['visible' => false]);

		$this->ControllerAction->setFieldOrder([
			'institution_site_position_id', 'role', 'start_date', 'position_type', 'FTE', 'staff_type_id', 'staff_status_id', 'security_user_id'
		]);
	}

	public function onUpdateFieldInstitutionSitePositionId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$institutionId = $this->Session->read('Institutions.id');
			$positionData = $this->Positions
			->find()
			->contain(['StaffPositionTitles'])
			->where([$this->Positions->aliasField('institution_site_id') => $institutionId])
			->order(['StaffPositionTitles.order'])
			->all();

			foreach ($positionData as $position) {
				$positionOptions[$position->id] = $position->name;
			}
			$attr['options'] = $positionOptions;
		}
		return $attr;
	}

	public function onUpdateFieldRole(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$Roles = TableRegistry::get('Security.SecurityRoles');
			$institutionId = $this->Session->read('Institutions.id');
			$institutionEntity = $this->Institutions->get($institutionId);
			$groupId = $institutionEntity->security_group_id;
			$this->ControllerAction->field('group_id', ['type' => 'hidden', 'value' => $groupId]);

			$roleOptions = [0 => '-- Select Role --'];
			$roleOptions = $roleOptions + $Roles->getPrivilegedRoleOptionsByGroup($groupId);
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
					['value' => '0.25', 'text' => '75%']
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
		if ($action == 'index') {
			$attr['sort'] = ['field' => 'Users.first_name'];
		} else if ($action == 'add') {
			$attr['type'] = 'autocomplete';
			$attr['target'] = ['key' => 'security_user_id', 'name' => $this->aliasField('security_user_id')];
			$attr['noResults'] = __('No Staff found');
			$attr['attr'] = ['placeholder' => __('OpenEMIS ID or Name')];
			$attr['url'] = ['controller' => 'Institutions', 'action' => 'Staff', 'ajaxUserAutocomplete'];
		}
		return $attr;
	}

	public function onGetSecurityUserId(Event $event, Entity $entity) {
		return $entity->_matchingData['Users']->name;
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

	public function afterAction(Event $event) {
		$this->ControllerAction->field('position_type');
		$this->ControllerAction->field('staff_type_id', ['type' => 'select', 'visible' => ['index' => false, 'view' => true, 'edit' => true]]);
		$this->ControllerAction->field('staff_status_id', ['type' => 'select']);
		$this->ControllerAction->field('security_user_id');

		if ($this->action == 'index') {
			// $table = TableRegistry::get('Institution.InstitutionSiteStudents');
			// $institutionSiteArray = [];
			// $session = $this->Session;
			// $institutionId = $session->read('Institutions.id');

			// // Get number of student in institution
			// $studentCount = $table->find()
			// 	->where([$table->aliasField('institution_site_id') => $institutionId])
			// 	->distinct(['security_user_id'])
			// 	->count(['security_user_id']);

			// // Get Gender
			// $institutionSiteArray['Gender'] = $table->getDonutChart('institution_site_student_gender', 
			// 	['institution_site_id' => $institutionId, 'key'=>'Gender']);

			// // Get Age
			// $institutionSiteArray['Age'] = $table->getDonutChart('institution_site_student_age', 
			// 	['conditions' => ['institution_site_id' => $institutionId], 'key'=>'Age']);

			// // Get Grades
			// $table = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
			// $institutionSiteArray['Grade'] = $table->getDonutChart('institution_site_section_student_grade', 
			// 	['conditions' => ['institution_site_id' => $institutionId], 'key'=>'Grade']);

			$indexDashboard = 'dashboard';
			$indexElements = $this->controller->viewVars['indexElements'];
			
			$indexElements[] = ['name' => 'Institution.Staff/controls', 'data' => [], 'options' => [], 'order' => 2];
			
			// $indexElements[] = [
	  //           'name' => $indexDashboard,
	  //           'data' => [
	  //           	'model' => 'students',
	  //           	'modelCount' => $studentCount,
	  //           	'modelArray' => $institutionSiteArray,
	  //           ],
	  //           'options' => [],
	  //           'order' => 1
	  //       ];
	        $this->controller->set('indexElements', $indexElements);
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
		$institutionId = $entity->institution_site_id;
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
			$UserTypes = $this->Users->UserTypes;
			$query = $UserTypes->find()->contain(['Users']);

			$term = trim($term);
			if (!empty($term)) {
				$query = $this->addSearchConditions($query, ['searchTerm' => $term]);
			}
			
			// only search for staff
			$query->where([$UserTypes->aliasField('user_type') => UserTypes::STAFF]);
			$list = $query->all();

			$data = array();
			foreach($list as $obj) {
				$data[] = [
					'label' => sprintf('%s - %s', $obj->user->openemis_no, $obj->user->name),
					'value' => $obj->user->id
				];
			}

			echo json_encode($data);
			die;
		}
	}
}
