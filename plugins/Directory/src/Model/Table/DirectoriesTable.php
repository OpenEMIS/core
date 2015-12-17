<?php
namespace Directory\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class DirectoriesTable extends AppTable {
	// public $InstitutionStudent;
	const ALL = 0;
	const STUDENT = 1;
	const STAFF = 2;
	const GUARDIAN = 3;
	const OTHER = 4;

	public function initialize(array $config) {
		$this->table('security_users');
		$this->entityClass('User.User');
		parent::initialize($config);

		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);

		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');
		$this->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' => ['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		$this->addBehavior('AdvanceSearch');

		$this->addBehavior('HighChart', [
			'user_gender' => [
				'_function' => 'getNumberOfUsersByGender'
			]
		]);

		// $this->addBehavior('Excel', [
		// 	'excludes' => ['photo_name', 'is_student', 'is_staff', 'is_guardian'],
		// 	'filename' => 'Students',
		// 	'pages' => ['view']
		// ]);

		$this->addBehavior('TrackActivity', ['target' => 'User.UserActivities', 'key' => 'security_user_id', 'session' => 'Directory.Directories.id']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator
			->allowEmpty('photo_content')
		;
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$userTypeOptions = [
				self::ALL => __('All Users'),
				self::STUDENT => __('Students'),
				self::STAFF => __('Staff'),
				self::GUARDIAN => __('Guardians'),
				self::OTHER => __('Others')
			];

		$selectedUserType = $this->queryString('user_type', $userTypeOptions);
		$this->advancedSelectOptions($userTypeOptions, $selectedUserType);
		$this->controller->set(compact('userTypeOptions'));

		$conditions = [];
		if (!is_null($request->query('user_type'))) {
			switch($request->query('user_type')) {
				case self::ALL:
					// Do nothing
					break;
				case self::STUDENT:
					$conditions = [$this->aliasField('is_student') => 1];
					break;

				case self::STAFF:
					$conditions = [$this->aliasField('is_staff') => 1];
					break;

				case self::GUARDIAN:
					$conditions = [$this->aliasField('is_guardian') => 1];
					break;

				case self::OTHER:
					$conditions = [
						$this->aliasField('is_student') => 0,
						$this->aliasField('is_staff') => 0,
						$this->aliasField('is_guardian') => 0
					];
					break;
			}
		}
		$notSuperAdminCondition = [
			$this->aliasField('super_admin') => 0
		];
		$conditions = array_merge($conditions, $notSuperAdminCondition);
		$query->where($conditions);

		$search = $this->ControllerAction->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->addSearchConditions($query, ['searchTerm' => $search]);
		}

		// this part filters the list by institutions/areas granted to the group
		if (!$this->AccessControl->isAdmin()) { // if user is not super admin, the list will be filtered
			$institutionIds = $this->AccessControl->getInstitutionsByUser();
			$this->Session->write('AccessControl.Institutions.ids', $institutionIds);
			
			$InstitutionStudentTable = TableRegistry::get('Institution.Students');

			$institutionStudents = $InstitutionStudentTable->find()
				->where([
					$InstitutionStudentTable->aliasField('institution_id').' IN ('.$InstitutionIds.')',
					$InstitutionStudentTable->aliasField('student_id').' = '.$this->aliasField('id')
				]);

			$InstitutionStaffTable = TableRegistry::get('Institution.Staff');

			$institutionStaff = $InstitutionStaffTable->find()
				->where([
					$InstitutionStudentTable->aliasField('institution_id').' IN ('.$InstitutionIds.')',
					$InstitutionStudentTable->aliasField('staff_id').' = '.$this->aliasField('id')
				]);

			$query->where([
					'OR' => [
						['EXISTS ('.$institutionStaff->sql().')'],
						['EXISTS ('.$institutionStudents->sql().')'],
					]
				])
				->group([$this->aliasField('id')]);
		}
	}

	public function afterAction(Event $event) {
		if ($this->action == 'index') {
			$conditions = [];
			if (!is_null($this->request->query('user_type'))) {
				switch($this->request->query('user_type')) {
					case self::ALL:
						// Do nothing
						break;
					case self::STUDENT:
						$conditions = [$this->aliasField('is_student') => 1];
						break;

					case self::STAFF:
						$conditions = [$this->aliasField('is_staff') => 1];
						break;

					case self::GUARDIAN:
						$conditions = [$this->aliasField('is_guardian') => 1];
						break;

					case self::OTHER:
						$conditions = [
							$this->aliasField('is_student') => 0,
							$this->aliasField('is_staff') => 0,
							$this->aliasField('is_guardian') => 0
						];
						break;
				}
			}
			$userCount = $this->find()->where($conditions);
			//Get Gender
			$userArray[__('Gender')] = $this->getDonutChart('user_gender', 
				['conditions' => $conditions, 'key' => __('Gender')]);

			$indexDashboard = 'dashboard';
			$indexElements = $this->controller->viewVars['indexElements'];
			
			$indexElements[] = ['name' => 'Directory.Users/controls', 'data' => [], 'options' => [], 'order' => 1];
			
			$indexElements[] = [
				'name' => $indexDashboard,
				'data' => [
					'model' => 'staff',
					'modelCount' => $userCount->count(),
					'modelArray' => $userArray,
				],
				'options' => [],
				'order' => 0
			];
			$this->controller->set('indexElements', $indexElements);
		}
	}

	public function addBeforeAction(Event $event) {
		$this->ControllerAction->field('user_type', ['type' => 'select']);
		$userType = $this->request->data[$this->alias()]['user_type'];
		$this->ControllerAction->field('openemis_no', ['user_type' => $userType]);
	}

	public function onUpdateFieldUserType(Event $event, array $attr, $action, Request $request) {
		$options = [
			self::STUDENT => __('Student'),
			self::STAFF => __('Staff'),
			self::GUARDIAN => __('Guardian'),
			self::OTHER => __('Others')
		];
		$attr['options'] = $options;
		$attr['onChangeReload'] = true;
		if (!isset($this->request->data[$this->alias()]['user_type'])) {
			$this->request->data[$this->alias()]['user_type'] = key($options);
		}
		return $attr;
	}

	public function onUpdateFieldOpenemisNo(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$options = [];
			if (isset($attr['user_type'])) {
				switch ($attr['user_type']) {
					case self::STUDENT:
						$options['model'] = 'Student';
						break;
					case self::STAFF:
						$options['model'] = 'Staff';
						break;
					case self::GUARDIAN:
						$options['model'] = 'Guardian';
						break;
				}
			}
			$value = $this->getUniqueOpenemisId($options);
			$attr['attr']['value'] = $value;
			$attr['value'] = $value;
			return $attr;
		}
	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions) {
		$userType = $requestData[$this->alias()]['user_type'];
		$type = [
			'is_student' => 0,
			'is_staff' => 0,
			'is_guardian' => 0
		];
		switch ($userType) {
			case self::STUDENT:
				$type['is_student'] = 1;
				break;
			case self::STAFF:
				$type['is_staff'] = 1;
				break;
			case self::GUARDIAN:
				$type['is_guardian'] = 1;
				break;
		}
		$directoryEntity = array_merge($requestData[$this->alias()], $type);
		$requestData[$this->alias()] = $directoryEntity;
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->fields = [];
		$this->ControllerAction->field('institution', ['order' => 50]);
	}

	public function getNumberOfUsersByGender($params=[]) {
		$conditions = isset($params['conditions']) ? $params['conditions'] : [];
		$userRecords = $this->find();
		$genderCount = $userRecords
			->contain(['Genders'])
			->select([
				'count' => $userRecords->func()->count($this->aliasField('id')),	
				'gender' => 'Genders.name'
			])
			->where($conditions)
			->group('gender');

		// Creating the data set		
		$dataSet = [];
		foreach ($genderCount->toArray() as $value) {
			//Compile the dataset
			if (is_null($value['gender'])) {
				$value['gender'] = 'Not Defined';
			}
			$dataSet[] = [__($value['gender']), $value['count']];
		}
		$params['dataSet'] = $dataSet;
		return $params;
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->Session->write('Directory.Directories.id', $entity->id);
		$this->Session->write('Directory.Directories.name', $entity->name);
		if (!$this->AccessControl->isAdmin()) {
			$institutionIds = $this->AccessControl->getInstitutionsByUser();
			$this->Session->write('AccessControl.Institutions.ids', $institutionIds);
		}
		$isStudent = $entity->is_student;
		$isStaff = $entity->is_staff;
		$isGuardian = $entity->is_guardian;
		$isSet = false;
		$this->Session->delete('Directory.Directories.is_student');
		$this->Session->delete('Directory.Directories.is_staff');
		$this->Session->delete('Directory.Directories.is_guardian');
		if ($isStudent) {
			$this->Session->write('Directory.Directories.is_student', true);
			$this->Session->write('Student.Students.id', $entity->id);
			$this->Session->write('Student.Students.name', $entity->name);
			$isSet = true;
		}

		if ($isStaff) {
			$this->Session->write('Directory.Directories.is_staff', true);
			$this->Session->write('Staff.Staff.id', $entity->id);
			$this->Session->write('Staff.Staff.name', $entity->name);
			$isSet = true;
		}

		// To make sure the navigation component has already read the set value
		if ($isSet) {
			$reload = $this->Session->read('Directory.Directories.reload');
			if (!isset($reload)) {
				$urlParams = $this->ControllerAction->url('view');
				$event->stopPropagation();
				return $this->controller->redirect($urlParams);
			}
		}

		$this->setupTabElements($entity);
	}

	private function setupTabElements($entity) {
		$id = !is_null($this->request->query('id')) ? $this->request->query('id') : 0;

		$options = [
			// 'userRole' => 'Student',
			// 'action' => $this->action,
			// 'id' => $id,
			// 'userId' => $entity->id
		];

		$tabElements = $this->controller->getUserTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function onGetInstitution(Event $event, Entity $entity) {
		$userId = $entity->id;
		$isStudent = $entity->is_student;
		$isStaff = $entity->is_staff;
		$isGuardian = $entity->is_guardian;

		$studentInstitutions = [];
		if ($isStudent) {
			$InstitutionStudentTable = TableRegistry::get('Institution.Students');
			$studentInstitutions = $InstitutionStudentTable->find('list', [
					'keyField' => 'id',
					'valueField' => 'name'
				])
				->matching('StudentStatuses')
				->matching('Institutions')
				->where([
					$InstitutionStudentTable->aliasField('student_id') => $userId,
					'StudentStatuses.code' => 'CURRENT'
				])
				->distinct(['id'])
				->select(['id' => $InstitutionStudentTable->aliasField('institution_id'), 'name' => 'Institutions.name'])
				->toArray();
		}

		$staffInstitutions = [];
		if ($isStaff) {
			$InstitutionStaffTable = TableRegistry::get('Institution.Staff');
			$staffInstitutions = $InstitutionStaffTable->find('list', [
					'keyField' => 'id',
					'valueField' => 'name'
				])
				->matching('Institutions')
				->select(['Institutions.name'])
				->where([$InstitutionStaffTable->aliasField('staff_id') => $userId])
				->andWhere([$InstitutionStaffTable->aliasField('end_date').' IS NULL'])
				->select(['id' => 'Institutions.id', 'name' => 'Institutions.name'])
				->toArray();
		}

		$combineArray = array_merge($studentInstitutions, $staffInstitutions);

		$value = implode('<BR>', $combineArray);

		return $value;
	}
}
