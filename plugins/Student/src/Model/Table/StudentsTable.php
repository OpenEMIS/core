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

class StudentsTable extends AppTable {
	public $InstitutionStudent;

	public function initialize(array $config) {
		$this->table('security_users');
		$this->entityClass('User.User');
		parent::initialize($config);

		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);

		$this->belongsToMany('Institutions', [
			'className' => 'Institution.Institutions',
			'joinTable' => 'institution_students',
			'foreignKey'	 => 'student_id',
			'targetForeignKey' => 'institution_id',
			'through' => 'Institution.Students',
			'dependent' => true
		]);

		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');
		$this->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' => ['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		$this->addBehavior('AdvanceSearch');

		$this->addBehavior('CustomField.Record', [
			'model' => 'Student.Students',
			'behavior' => 'Student',
			'fieldKey' => 'student_custom_field_id',
			'tableColumnKey' => 'student_custom_table_column_id',
			'tableRowKey' => 'student_custom_table_row_id',
			'formKey' => 'student_custom_form_id',
			'filterKey' => 'student_custom_filter_id',
			'formFieldClass' => ['className' => 'StudentCustomField.StudentCustomFormsFields'],
			'formFilterClass' => ['className' => 'StudentCustomField.StudentCustomFormsFilters'],
			'recordKey' => 'security_user_id',
			'fieldValueClass' => ['className' => 'StudentCustomField.StudentCustomFieldValues', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => ['className' => 'StudentCustomField.StudentCustomTableCells', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]
		]);

		$this->addBehavior('Excel', [
			'excludes' => ['photo_name', 'is_student', 'is_staff', 'is_guardian'],
			'filename' => 'Students',
			'pages' => ['view']
		]);

		$this->addBehavior('HighChart', [
			'number_of_students_by_year' => [
				'_function' => 'getNumberOfStudentsByYear',
				'chart' => ['type' => 'column', 'borderWidth' => 1],
				'xAxis' => ['title' => ['text' => 'Years']],
				'yAxis' => ['title' => ['text' => 'Total']]
			],
			'count_by_gender' => [
				'_function' => 'getNumberOfStudentsByGender'
			]
		]);

		// $this->addBehavior('TrackActivity', ['target' => 'Student.StudentActivities', 'key' => 'security_user_id', 'session' => 'Users.id']);

		$this->InstitutionStudent = TableRegistry::get('Institution.Students');
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

		$this->setValidationCode('first_name.ruleCheckIfStringGotNoNumber', 'User.Users');
		$this->setValidationCode('first_name.ruleNotBlank', 'User.Users');
		$this->setValidationCode('last_name.ruleCheckIfStringGotNoNumber', 'User.Users');
		$this->setValidationCode('openemis_no.ruleUnique', 'User.Users');
		$this->setValidationCode('username.ruleUnique', 'User.Users');
		$this->setValidationCode('username.ruleAlphanumeric', 'User.Users');
		return $validator;
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		// to set the student name in headers
		$this->Session->write('Student.Students.name', $entity->name);
		$this->request->data[$this->alias()]['student_id'] = $entity->id;
		$this->setupTabElements(['id' => $entity->id]);
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		// fields are set in UserBehavior
		$this->fields = []; // unset all fields first

		//find out current academic period and store it in session
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods')->getCurrent();
		if(!empty($AcademicPeriod)) {
			$session = $this->request->session();
			$session->write('Student.AcademicPeriod.Current.id', $AcademicPeriod);
		}

		$this->ControllerAction->field('institution', ['order' => 50]);
		$this->ControllerAction->field('status', ['order' => 51, 'sort' => false]);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->where([$this->aliasField('is_student') => 1]);

		$search = $this->ControllerAction->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->addSearchConditions($query, ['searchTerm' => $search]);
		}

		// this part filters the list by institutions/areas granted to the group
		if (!$this->AccessControl->isAdmin()) { // if user is not super admin, the list will be filtered
			$institutionIds = $this->AccessControl->getInstitutionsByUser();
			$this->Session->write('AccessControl.Institutions.ids', $institutionIds);
			$query->innerJoin(
				['InstitutionStudent' => 'institution_students'],
				[
					'InstitutionStudent.student_id = ' . $this->aliasField($this->primaryKey()),
					'InstitutionStudent.institution_id IN ' => $institutionIds
				]
			)
			->group([$this->aliasField('id')]);
			;
		}
	}

	public function onGetInstitution(Event $event, Entity $entity) {
		$userId = $entity->id;

		$session = $this->request->session();
		$currentAcademicPeriod = $session->read('Student.AcademicPeriod.Current.id');

		$selectedStudentStatusId = 1; //Related to task PHPOE-1872

		$query = $this->InstitutionStudent->find()
						 ->contain(['Institutions'])
						 ->matching('StudentStatuses', function ($q) use ($selectedStudentStatusId) {
						    return $q->where(['StudentStatuses.id' => $selectedStudentStatusId]);
						 })	
						 ->where([$this->InstitutionStudent->aliasField('student_id') => $userId])
						 ->andWhere([$this->InstitutionStudent->aliasField('academic_period_id') => $currentAcademicPeriod])
						 ->order([$this->InstitutionStudent->aliasField('start_date') => 'DESC'])
						 ;	

		$value = '';
		if ($query->count() > 0) {
			$results = $query
				->all()
				->toArray();

			$institutionArr = [];
			foreach ($results as $key => $obj) {
				$institutionArr[$obj->institution->id] = $obj->institution->name;
			}
			$value = implode('<BR>', $institutionArr);
			$studentStatus = $query->first()->_matchingData['StudentStatuses'];
			$entity->student_status = $studentStatus->name;
			$entity->status_code = $studentStatus->code;
		}
		return $value;
	}

	public function onGetStatus(Event $event, Entity $entity) {
		$value = ' ';
		if ($entity->has('student_status')) {
			$value = $entity->student_status;
		}
		return $value;
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		return $events;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		switch ($action) {
			case 'view':
				if (!$this->AccessControl->isAdmin()) {
					$institutionIds = $this->Session->read('AccessControl.Institutions.ids');
					$studentId = $this->request->data[$this->alias()]['student_id'];
					$enrolledStatus = false;
					$InstitutionStudentsTable = TableRegistry::get('Institution.Students');
					foreach ($institutionIds as $id) {
						$enrolledStatus = $InstitutionStudentsTable->checkEnrolledInInstitution($studentId, $id);
						if ($enrolledStatus) {
							break;
						}
					}
					if (! $enrolledStatus) {
						if (isset($toolbarButtons['edit'])) {
							unset($toolbarButtons['edit']);
						}
					}
				}
				break;
			case 'index':
				$toolbarButtons['import'] = $toolbarButtons['add'];
				$toolbarButtons['import']['url']['action'] = 'Import';
				$toolbarButtons['import']['attr']['title'] = __('Import');
				$toolbarButtons['import']['label'] = '<i class="fa kd-import"></i>';	
				break;
		}
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		if (!$this->AccessControl->isAdmin()) {
			if ($entity->status_code != 'CURRENT') {
				if (isset($buttons['edit'])) {
					unset($buttons['edit']);
				}
				if (isset($buttons['remove'])) {
					unset($buttons['remove']);
				}
			}
		}
		return $buttons;
	}

	public function addBeforeAction(Event $event) {
		$openemisNo = $this->getUniqueOpenemisId(['model' => Inflector::singularize('Student')]);
		$this->ControllerAction->field('openemis_no', [ 
			'attr' => ['value' => $openemisNo],
			'value' => $openemisNo
		]);

		$this->ControllerAction->field('username', ['order' => 70]);
		$this->ControllerAction->field('password', ['order' => 71, 'visible' => true]);
		$this->ControllerAction->field('is_student', ['value' => 1]);
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		$process = function($model, $id, $options) {
			$model->updateAll(['is_student' => 0], [$model->primaryKey() => $id]);
			return true;
		};
		return $process;
	}
	
	// Logic for the mini dashboard
	public function afterAction(Event $event) {
		if ($this->action == 'index') {
			// Get total number of students
			$count = $this->find()->where([$this->aliasField('is_student') => 1])->count();

			// Get the gender for all students
			$data = [];
			$data[__('Gender')] = $this->getDonutChart('count_by_gender', ['key' => __('Gender')]);

			$indexDashboard = 'dashboard';
			$this->controller->viewVars['indexElements']['mini_dashboard'] = [
	            'name' => $indexDashboard,
	            'data' => [
	            	'model' => 'students',
	            	'modelCount' => $count,
	            	'modelArray' => $data,
	            ],
	            'options' => [],
	            'order' => 1
	        ];
	    }
	}
	
	private function setupTabElements($options) {
		$this->controller->set('selectedAction', $this->alias);
		$this->controller->set('tabElements', $this->controller->getUserTabElements($options));
	}

	// Function use by the mini dashboard (For Student.Students)
	public function getNumberOfStudentsByGender($params=[]) {
		$query = $this->find();
		$query
		->select(['gender_id', 'count' => $query->func()->count($this->aliasField($this->primaryKey()))])
		->where([$this->aliasField('is_student') => 1])
		->group('gender_id')
		;

		$genders = $this->Genders->getList()->toArray();

		$resultSet = $query->all();
		foreach ($resultSet as $entity) {
			$dataSet[] = [__($genders[$entity['gender_id']]), $entity['count']];
		}
		$params['dataSet'] = $dataSet;
		return $params;
	}
}
