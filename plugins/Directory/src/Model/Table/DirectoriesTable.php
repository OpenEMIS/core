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

	// these constants are being used in AdvancedPositionSearchBehavior as well
	// remember to check AdvancedPositionSearchBehavior if these constants are being modified
	const ALL = 0;
	const STUDENT = 1;
    const STAFF = 2;
	const GUARDIAN = 3;
	const OTHER = 4;
    const STUDENTNOTINSCHOOL = 5;
    const STAFFNOTINSCHOOL = 6;

	private $dashboardQuery;

	public function initialize(array $config) {
		$this->table('security_users');
		$this->entityClass('User.User');
		parent::initialize($config);

		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);

		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');
		$this->addBehavior('AdvanceSearch');
		$this->addBehavior('Security.UserCascade'); // for cascade delete on user related tables
		$this->addBehavior('User.AdvancedIdentitySearch');
		$this->addBehavior('User.AdvancedContactNumberSearch');
		$this->addBehavior('User.AdvancedPositionSearch');
		$this->addBehavior('User.AdvancedSpecificNameTypeSearch');

		$this->addBehavior('HighChart', [
			'user_gender' => [
				'_function' => 'getNumberOfUsersByGender'
			]
		]);
        $this->addBehavior('Import.ImportLink', ['import_model'=>'ImportUsers']);
		// $this->addBehavior('Excel', [
		// 	'excludes' => ['photo_name', 'is_student', 'is_staff', 'is_guardian'],
		// 	'filename' => 'Students',
		// 	'pages' => ['view']
		// ]);

		$this->addBehavior('TrackActivity', ['target' => 'User.UserActivities', 'key' => 'security_user_id', 'session' => 'Directory.Directories.id']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		$BaseUsers = TableRegistry::get('User.Users');
		return $BaseUsers->setUserValidation($validator, $this);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {


        if ($this->AccessControl->isAdmin()) { // if user is super admin, this condition is used for filtering
            $userTypeOptions = [
                self::STAFF => __('Staff'),
                self::STUDENT => __('Students'),
                self::GUARDIAN => __('Guardians'),
                self::OTHER => __('Others')
            ];
        } else {
            $userTypeOptions = [
                __('In School') => [
                    self::STAFF => __('Staff'),
                    self::STUDENT => __('Students'),
                ],
                __('Not In School') => [
                    self::STAFFNOTINSCHOOL => __('Staff'),
                    self::STUDENTNOTINSCHOOL => __('Student')
                ]
            ];
        }

		$selectedUserType = $this->queryString('user_type', $userTypeOptions);
		$this->advancedSelectOptions($userTypeOptions, $selectedUserType);
		$this->controller->set(compact('userTypeOptions'));

		$conditions = [];

        if ($this->AccessControl->isAdmin()) { // if user is super admin, this condition is used for filtering
    		if (!is_null($request->query('user_type'))) {
    			switch($request->query('user_type')) {
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
			$institutionIds = implode(', ', $institutionIds);
			$this->Session->write('AccessControl.Institutions.ids', $institutionIds);

            if (!is_null($request->query('user_type'))) {
                switch($request->query('user_type')) {
                    case self::STUDENT:
                        $query->find('StudentsInSchool', ['institutionIds' => $institutionIds]);
                        break;

                    case self::STUDENTNOTINSCHOOL:
                        $query->find('StudentsNotInSchool');
                        break;

                    case self::STAFF:
                        $query->find('StaffInSchool', ['institutionIds' => $institutionIds]);
                        break;

                    case self::STAFFNOTINSCHOOL:
                        $query->find('StaffNotInSchool');
                        break;
                }
            }
		}

		$this->dashboardQuery = clone $query;
	}

    public function findStudentsInSchool(Query $query, array $options)
    {
        $institutionIds = (array_key_exists('institutionIds', $options))? $options['institutionIds']: [];
        if (!empty($institutionIds)) {
            $query->join([
                [
                    'type' => 'INNER',
                    'table' => 'institution_students',
                    'alias' => 'InstitutionStudents',
                    'conditions' => [
                        'InstitutionStudents.institution_id'.' IN ('.$institutionIds.')',
                        'InstitutionStudents.student_id = '. $this->aliasField('id')
                    ]
                ]
            ]);
        } else {
            // return nothing if $institutionIds is empty
            $query->where([$this->aliasField('id') => -1]);
        }

        return $query;
    }

    public function findStudentsNotInSchool(Query $query, array $options)
    {
        $InstitutionStudentTable = TableRegistry::get('Institution.Students');
        $allInstitutionStudents = $InstitutionStudentTable->find()
            ->select([
                $InstitutionStudentTable->aliasField('student_id')
            ])
            ->where([
                $InstitutionStudentTable->aliasField('student_id').' = '.$this->aliasField('id')
            ])
            ->bufferResults(false);
        $query->where(['NOT EXISTS ('.$allInstitutionStudents->sql().')', $this->aliasField('is_student') => 1]);
        return $query;
    }

    public function findStaffInSchool(Query $query, array $options)
    {
        $institutionIds = (array_key_exists('institutionIds', $options))? $options['institutionIds']: [];
        if (!empty($institutionIds)) {
            $query->join([
                [
                    'type' => 'INNER',
                    'table' => 'institution_staff',
                    'alias' => 'InstitutionStaff',
                    'conditions' => [
                        'InstitutionStaff.institution_id'.' IN ('.$institutionIds.')',
                        'InstitutionStaff.staff_id = '. $this->aliasField('id')
                    ]
                ]
            ]);
        } else {
            // return nothing if $institutionIds is empty
            $query->where([$this->aliasField('id') => -1]);
        }

        return $query;
    }

    public function findStaffNotInSchool(Query $query, array $options)
    {
        $InstitutionStaffTable = TableRegistry::get('Institution.Staff');
        $allInstitutionStaff = $InstitutionStaffTable->find()
            ->select([
                $InstitutionStaffTable->aliasField('staff_id')
            ])
            ->where([
                $InstitutionStaffTable->aliasField('staff_id').' = '.$this->aliasField('id')
            ])
            ->bufferResults(false);
            $query->where(['NOT EXISTS ('.$allInstitutionStaff->sql().')', $this->aliasField('is_staff') => 1]);
        return $query;
    }

	public function afterAction(Event $event) {
		if ($this->action == 'index') {
			$iconClass = '';
			if (!is_null($this->request->query('user_type'))) {
				switch($this->request->query('user_type')) {
					case self::ALL:
						// Do nothing
						$dashboardModel = 'users';
						$iconClass = 'fa fa-user';
						break;
					case self::STUDENT: case self::STUDENTNOTINSCHOOL:
						$dashboardModel = 'students';
						break;

					case self::STAFF: case self::STAFFNOTINSCHOOL:
						$dashboardModel = 'staff';
						break;

					case self::GUARDIAN:
						$dashboardModel = 'guardians';
						$iconClass = 'kd-guardian';
						break;

					case self::OTHER:
						$dashboardModel = 'others';
						$iconClass = 'fa fa-user';
						break;
				}
			}
			$userCount = $this->dashboardQuery;
			//Get Gender
			$userArray[__('Gender')] = $this->getDonutChart('user_gender',
				['query' => $this->dashboardQuery, 'key' => __('Gender')]);

			$indexDashboard = 'dashboard';
			$indexElements = $this->controller->viewVars['indexElements'];

			$indexElements[] = ['name' => 'Directory.Users/controls', 'data' => [], 'options' => [], 'order' => 0];

			$indexElements[] = [
				'name' => $indexDashboard,
				'data' => [
					'model' => $dashboardModel,
					'modelCount' => $userCount->count(),
					'modelArray' => $userArray,
					'iconClass' => $iconClass
				],
				'options' => [],
				'order' => 2
			];
			foreach ($indexElements as $key => $value) {
				if ($value['name']=='advanced_search') {
					$indexElements[$key]['order'] = 1;
				} else if ($value['name']=='OpenEmis.ControllerAction/index') {
					$indexElements[$key]['order'] = 3;
				} else if ($value['name']=='OpenEmis.pagination') {
					$indexElements[$key]['order'] = 4;
				} else {
					// $indexElements[$key]['order'] = $key + 4;
				}
			}
			$this->controller->set('indexElements', $indexElements);
		}
	}

	public function beforeAction(Event $event) {
		if ($this->action == 'add') {
			if ($this->controller->name != 'Students') {
				$this->ControllerAction->field('user_type', ['type' => 'select', 'after' => 'photo_content']);
			} else {
				$this->request->query['user_type'] = self::GUARDIAN;
			}
			$userType = isset($this->request->data[$this->alias()]['user_type']) ? $this->request->data[$this->alias()]['user_type'] : $this->request->query('user_type');
			$this->ControllerAction->field('openemis_no', ['user_type' => $userType]);

			switch ($userType) {
				case self::STUDENT:
					$this->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' => ['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
					$this->addBehavior('CustomField.Record', [
						'model' => 'Student.Students',
						'behavior' => 'Student',
						'fieldKey' => 'student_custom_field_id',
						'tableColumnKey' => 'student_custom_table_column_id',
						'tableRowKey' => 'student_custom_table_row_id',
						'fieldClass' => ['className' => 'StudentCustomField.StudentCustomFields'],
						'formKey' => 'student_custom_form_id',
						'filterKey' => 'student_custom_filter_id',
						'formFieldClass' => ['className' => 'StudentCustomField.StudentCustomFormsFields'],
						'formFilterClass' => ['className' => 'StudentCustomField.StudentCustomFormsFilters'],
						'recordKey' => 'student_id',
						'fieldValueClass' => ['className' => 'StudentCustomField.StudentCustomFieldValues', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true],
						'tableCellClass' => ['className' => 'StudentCustomField.StudentCustomTableCells', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true]
					]);
					break;
				case self::STAFF:
					$this->addBehavior('User.Mandatory', ['userRole' => 'Staff', 'roleFields' =>['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
					$this->addBehavior('CustomField.Record', [
						'model' => 'Staff.Staff',
						'behavior' => 'Staff',
						'fieldKey' => 'staff_custom_field_id',
						'tableColumnKey' => 'staff_custom_table_column_id',
						'tableRowKey' => 'staff_custom_table_row_id',
						'fieldClass' => ['className' => 'StaffCustomField.StaffCustomFields'],
						'formKey' => 'staff_custom_form_id',
						'filterKey' => 'staff_custom_filter_id',
						'formFieldClass' => ['className' => 'StaffCustomField.StaffCustomFormsFields'],
						'formFilterClass' => ['className' => 'StaffCustomField.StaffCustomFormsFilters'],
						'recordKey' => 'staff_id',
						'fieldValueClass' => ['className' => 'StaffCustomField.StaffCustomFieldValues', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true],
						'tableCellClass' => ['className' => 'StaffCustomField.StaffCustomTableCells', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true]
					]);
					break;
			}
		} else if ($this->action == 'edit') {
			$this->hideOtherInformationSection($this->controller->name, 'edit');
		}
	}

	public function hideOtherInformationSection($controller, $action)
	{
		if (($action=="add") || ($action=="edit")) { //hide "other information" section on add/edit guardian because there wont be any custom field.
			if (($controller=="Students") || ($controller=="Directories")) {
				$this->ControllerAction->field('other_information_section', ['visible' => false]);
			}
		}
	}

    public function addBeforeAction(Event $event)
    {
        if (!isset($this->request->data[$this->alias()]['user_type'])) {
            $this->request->data[$this->alias()]['user_type'] = $this->request->query('user_type');
        }
    }

	public function addAfterAction(Event $event) {
		// need to find out order values because recordbehavior changes it
		$allOrderValues = [];
		foreach ($this->fields as $key => $value) {
			$allOrderValues[] = (array_key_exists('order', $value) && !empty($value['order']))? $value['order']: 0;
		}
		$highestOrder = max($allOrderValues);

		$userType = $this->request->query('user_type');

		switch ($userType) {
			case self::STUDENT:
				// do nothing
				break;
			default:
				$this->ControllerAction->field('username', ['order' => ++$highestOrder, 'visible' => true]);
				$this->ControllerAction->field('password', ['order' => ++$highestOrder, 'visible' => true, 'type' => 'password', 'attr' => ['value' => '', 'autocomplete' => 'off']]);
				break;
		}
	}

	public function onUpdateFieldUserType(Event $event, array $attr, $action, Request $request) {
		$options = [
			self::STUDENT => __('Student'),
			self::STAFF => __('Staff'),
			self::GUARDIAN => __('Guardian'),
			self::OTHER => __('Others')
		];
		$attr['options'] = $options;
		$attr['onChangeReload'] = 'changeUserType';
		if (!$this->request->query('user_type')) {
			$this->request->query['user_type'] = key($options);
		}
		return $attr;
	}

    public function addOnChangeUserType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		unset($this->request->query['user_type']);

		if ($this->request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $data)) {
				if (array_key_exists('user_type', $data[$this->alias()])) {
					$this->request->query['user_type'] = $data[$this->alias()]['user_type'];
				}
			}
		}
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
			'is_student' => '0',
			'is_staff' => '0',
			'is_guardian' => '0'
			// 'is_student' => intval(0),
			// 'is_staff' => intval(0),
			// 'is_guardian' => intval(0)
			// 'is_student' => 0,
			// 'is_staff' => 0,
			// 'is_guardian' => 0
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

	public function indexBeforeAction(Event $event, ArrayObject $settings) {
		$this->fields = [];
		$this->controller->set('ngController', 'AdvancedSearchCtrl');

		if (!is_null($this->request->query('user_type'))) {
			switch($this->request->query('user_type')) {
				case self::ALL:
					// Do nothing
					break;
				case self::STUDENT:
					$this->ControllerAction->field('institution', ['order' => 50]);
					$this->ControllerAction->field('student_status', ['order' => 51]);
					break;

				case self::STAFF:
					$this->ControllerAction->field('institution', ['order' => 50]);
					break;

				case self::GUARDIAN:

					break;

				case self::OTHER:

					break;
			}
		}
	}

	public function onGetStudentStatus(Event $event, Entity $entity) {
		return __($entity->student_status_name);
	}

	public function getNumberOfUsersByGender($params=[]) {
		$query = isset($params['query']) ? $params['query'] : null;
		if (!is_null($query)) {
			$userRecords = clone $query;
		} else {
			$userRecords = $this->find();
		}
		$genderCount = $userRecords
			->contain(['Genders'])
			->select([
				'count' => $userRecords->func()->count($this->aliasField('id')),
				'gender' => 'Genders.name'
			])
			->group('gender', true)
			->bufferResults(false);

		// Creating the data set
		$dataSet = [];
		foreach ($genderCount as $value) {
			//Compile the dataset
			if (is_null($value['gender'])) {
				$value['gender'] = 'Not Defined';
			}
			$dataSet[] = [__($value['gender']), $value['count']];
		}
		$params['dataSet'] = $dataSet;
		return $params;
	}

	private function setSessionAfterAction($event, $entity){

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

		return $isSet;

	}

	public function editAfterAction(Event $event, Entity $entity) {

		$isSet = $this->setSessionAfterAction($event, $entity);

		if ($isSet) {
			$reload = $this->Session->read('Directory.Directories.reload');
			if (!isset($reload)) {
				$urlParams = $this->ControllerAction->url('edit');
				$event->stopPropagation();
				return $this->controller->redirect($urlParams);
			}
		}

		$this->setupTabElements($entity);
	}

	public function viewAfterAction(Event $event, Entity $entity) {

		$isSet = $this->setSessionAfterAction($event, $entity);

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
			$studentInstitutions = $InstitutionStudentTable->find()
				->matching('StudentStatuses')
				->matching('Institutions')
				->where([
					$InstitutionStudentTable->aliasField('student_id') => $userId,
				])
				->distinct(['id'])
				->select(['id' => $InstitutionStudentTable->aliasField('institution_id'), 'name' => 'Institutions.name', 'student_status_name' => 'StudentStatuses.name'])
				->order(['(CASE WHEN '.$InstitutionStudentTable->aliasField('modified').' IS NOT NULL THEN '.$InstitutionStudentTable->aliasField('modified').' ELSE '.
				$InstitutionStudentTable->aliasField('created').' END) DESC'])
				->first();

			$value = '';
			$name = '';
			if (!empty($studentInstitutions)) {
				$value = $studentInstitutions->student_status_name;
				$name = $studentInstitutions->name;
			}
			$entity->student_status_name = $value;

			return $name;
		}

		$staffInstitutions = [];
		if ($isStaff) {
			$InstitutionStaffTable = TableRegistry::get('Institution.Staff');
			$today = date('Y-m-d');
			$staffInstitutions = $InstitutionStaffTable->find('list', [
					'keyField' => 'id',
					'valueField' => 'institutionName'
				])
				->find('inDateRange', ['start_date' => $today, 'end_date' => $today])
				->matching('Institutions')
				->where([$InstitutionStaffTable->aliasField('staff_id') => $userId])
				->select(['id' => 'Institutions.id', 'institutionName' => 'Institutions.name'])
				->group(['Institutions.id'])
				->order(['Institutions.name'])
				->toArray();
			return implode('<BR>', $staffInstitutions);
		}
	}
}
