<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Security\Model\Table\SecurityUserTypesTable as UserTypes;

class StudentsTable extends AppTable {
	// used for type = image
	private $defaultImgIndexClass = "profile-image-thumbnail";
	private $defaultImgViewClass= "profile-image";
	private $defaultImgMsg = "<p>* Advisable photo dimension 90 by 115px<br>* Format Supported: .jpg, .jpeg, .png, .gif </p>";
	private $defaultStudentProfileView = "<div class='profile-image'><i class='kd-students'></i></div>";

	public $InstitutionStudent;

	public function initialize(array $config) {
		$this->table('security_users');
		$this->entityClass('User.User');
		parent::initialize($config);

		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);

		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');
		$this->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' =>['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		// $this->addBehavior('Institution.User', ['associatedModel' => $this->InstitutionSiteStudents]);
		$this->addBehavior('AdvanceSearch');

		$this->addBehavior('HighChart', [
			'number_of_students_by_year' => [
				'_function' => 'getNumberOfStudentsByYear',
				'chart' => ['type' => 'column', 'borderWidth' => 1],
				'xAxis' => ['title' => ['text' => 'Years']],
				'yAxis' => ['title' => ['text' => 'Total']]
			],
			'institution_site_student_gender' => [
				'_function' => 'getNumberOfStudentsByGender'
			],
			'institution_site_student_age' => [
				'_function' => 'getNumberOfStudentsByAge'
			]
		]);

		$this->InstitutionStudent = TableRegistry::get('Institution.Students');
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	// $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	// public function beforeAction(Event $event) {

	// }

	public function viewAfterAction(Event $event, Entity $entity) {
		// to set the student name in headers
		$this->request->session()->write('Students.name', $entity->name);
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$settings['model'] = 'Security.SecurityUserTypes';
		$this->fields = []; // unset all fields first

		$this->ControllerAction->field('institution', ['order' => 50]);
		$this->ControllerAction->field('status', ['order' => 51]);
	}

	public function onGetInstitution(Event $event, Entity $entity) {
		$userId = $entity->security_user_id;
		$query = $this->InstitutionStudent->find()
		->contain(['Institutions', 'StudentStatuses'])
		->where([$this->InstitutionStudent->aliasField('student_id') => $userId])
		->order([$this->InstitutionStudent->aliasField('start_date') => 'DESC'])
		;

		$value = '';
		if ($query->count() > 0) {
			$obj = $query->first();
			$value = $obj->institution->name;
			$entity->status = $obj->student_status->name;
		}
		return $value;
	}

	public function onGetStatus(Event $event, Entity $entity) {
		$value = '';
		if ($entity->has('status')) {
			$value = $entity->status;
		}
		return $value;
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->contain(['Users']);
		$query->where(['SecurityUserTypes.user_type' => UserTypes::STUDENT]);

		$search = $this->ControllerAction->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->addSearchConditions($query, ['searchTerm' => $search]);
		}
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		$UserTypes = TableRegistry::get('Security.SecurityUserTypes');

        if ($entity->isNew()) {
			$obj = $UserTypes->newEntity(['security_user_id' => $entity->id, 'user_type' => UserTypes::STUDENT]);
			$UserTypes = $UserTypes->save($obj);
        }
	}
	
	// Logic for the mini dashboard
	public function afterAction(Event $event) {
		if ($this->action == 'index') {
			$userTypes = TableRegistry::get('Security.SecurityUserTypes');
			$institutionSiteArray = [];

			// Get total number of students
			$count = $userTypes->find()
				->distinct(['security_user_id'])
				->where([$userTypes->aliasField('user_type') => UserTypes::STUDENT])
				->count(['security_user_id']);

			// Get the gender for all students
			$institutionSiteArray['Gender'] = $this->getDonutChart('institution_site_student_gender', ['key' => 'Gender']);

			$indexDashboard = 'dashboard';
			$this->controller->viewVars['indexElements']['mini_dashboard'] = [
	            'name' => $indexDashboard,
	            'data' => [
	            	'model' => 'students',
	            	'modelCount' => $count,
	            	'modelArray' => $institutionSiteArray,
	            ],
	            'options' => [],
	            'order' => 1
	        ];
	    }
	}

	public function addBeforeAction(Event $event) {
		$openemisNo = $this->getUniqueOpenemisId(['model' => Inflector::singularize('Student')]);
		$this->ControllerAction->field('openemis_no', [ 
			'attr' => ['value' => $openemisNo],
			'value' => $openemisNo
		]);

		$this->ControllerAction->field('username', ['order' => 100]);
		$this->ControllerAction->field('password', ['order' => 101, 'visible' => true]);
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		$userTypes = TableRegistry::get('Security.SecurityUserTypes');
		$affectedRows = $userTypes->deleteAll([
			'security_user_id' => $entity->id,
			'user_type' => UserTypes::STUDENT
		]);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->add('first_name', [
					'ruleCheckIfStringGotNoNumber' => [
						'rule' => 'checkIfStringGotNoNumber',
					],
					'ruleNotBlank' => [
						'rule' => 'notBlank',
					]
				])
			->add('last_name', [
					'ruleCheckIfStringGotNoNumber' => [
						'rule' => 'checkIfStringGotNoNumber',
					]
				])
			->add('openemis_no', [
					'ruleUnique' => [
						'rule' => 'validateUnique',
						'provider' => 'table',
					]
				])
			->add('username', [
				'ruleUnique' => [
					'rule' => 'validateUnique',
					'provider' => 'table',
				],
				'ruleAlphanumeric' => [
				    'rule' => 'alphanumeric',
				]
			])
			->allowEmpty('username')
			->allowEmpty('password')
			->allowEmpty('photo_content')
			;
		return $validator;
	}

	public function getDefaultImgMsg() {
		return $this->defaultImgMsg;
	}

	public function getDefaultImgIndexClass() {
		return $this->defaultImgIndexClass;
	}

	public function getDefaultImgViewClass() {
		return $this->defaultImgViewClass;
	}

	public function getDefaultImgView() {
		return $this->defaultStudentProfileView;;
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		foreach (['view', 'edit'] as $action) {
			if (array_key_exists($action, $buttons)) {
				$buttons[$action]['url'][1] = $entity->security_user_id;
			}
		}
		if (array_key_exists('remove', $buttons)) {
			$buttons['remove']['attr']['field-value'] = $entity->security_user_id;
		}
		return $buttons;
	}

	// used by highchart
	public function getNumberOfStudentsByYear($params=[]) {
		$conditions = isset($params['conditions']) ? $params['conditions'] : [];
		$_conditions = [];
		foreach ($conditions as $key => $value) {
			$_conditions['InstitutionSiteStudents.'.$key] = $value;
		}

		$periodConditions = $_conditions;
		$query = $this->find();
		$periodResult = $query
			->select([
				'min_year' => $query->func()->min('InstitutionSiteStudents.start_year'),
				'max_year' => $query->func()->max('InstitutionSiteStudents.end_year')
			])
			->where($periodConditions)
			->first();
		$AcademicPeriod = $this->Institutions->InstitutionSiteProgrammes->AcademicPeriods;
		$currentPeriodId = $AcademicPeriod->getCurrent();
		$currentPeriodObj = $AcademicPeriod->get($currentPeriodId);
		$thisYear = $currentPeriodObj->end_year;
		$minYear = $thisYear - 2;
		$minYear = $minYear > $periodResult->min_year ? $minYear : $periodResult->min_year;
		$maxYear = $thisYear;

		$years = [];

		$genderOptions = $this->Genders->getList();
		$dataSet = [];
		foreach ($genderOptions as $key => $value) {
			$dataSet[$value] = ['name' => __($value), 'data' => []];
		}

		$studentsByYearConditions = array('Genders.name IS NOT NULL');
		$studentsByYearConditions = array_merge($studentsByYearConditions, $_conditions);

		for ($currentYear = $minYear; $currentYear <= $maxYear; $currentYear++) {
			$years[$currentYear] = $currentYear;
			$studentsByYearConditions['OR'] = [
				[
					'InstitutionSiteStudents.end_year IS NOT NULL',
					'InstitutionSiteStudents.start_year <= "' . $currentYear . '"',
					'InstitutionSiteStudents.end_year >= "' . $currentYear . '"'
				]
			];

			$query = $this->find();
			$studentsByYear = $query
				->contain(['Users.Genders'])
				->select([
					'Users.first_name',
					'Genders.name',
					'total' => $query->func()->count('InstitutionSiteStudents.id')
				])
				->where($studentsByYearConditions)
				->group('Genders.name')
				->toArray()
				;
 			foreach ($dataSet as $key => $value) {
 				if (!array_key_exists($currentYear, $dataSet[$key]['data'])) {
 					$dataSet[$key]['data'][$currentYear] = 0;
 				}				
			}

			foreach ($studentsByYear as $key => $studentByYear) {
				$studentGender = isset($studentByYear->user->gender->name) ? $studentByYear->user->gender->name : null;
				$studentTotal = isset($studentByYear->total) ? $studentByYear->total : 0;
				$dataSet[$studentGender]['data'][$currentYear] = $studentTotal;
			}
		}

		$params['dataSet'] = $dataSet;
		
		return $params;
	}

	// Function use by the mini dashboard (For Institution Students and Students)
	public function getNumberOfStudentsByGender($params=[]) {
		$institutionSiteRecords = $this->find();
		$institutionSiteStudentCount = $institutionSiteRecords
			->select([
				'count' => $institutionSiteRecords->func()->count('DISTINCT Students.id'),	
				'gender' => 'Genders.name'
			])
			->contain(['Genders'])
			->innerJoin(['UserTypes' => 'security_user_types'], [
				'UserTypes.security_user_id = Students.id',
				'UserTypes.user_type' => UserTypes::STUDENT
			])
			->group('gender');

		if (!empty($params['institution_site_id'])) {
			$institutionSiteStudentCount->where(['institution_site_id' => $params['institution_site_id']]);
		}	
		// Creating the data set		
		$dataSet = [];
		foreach ($institutionSiteStudentCount->toArray() as $value) {
			//Compile the dataset
			$dataSet[] = [$value['gender'], $value['count']];
		}
		$params['dataSet'] = $dataSet;
		return $params;
	}
}
