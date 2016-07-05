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
use User\Model\Table\UsersTable AS BaseUsers;

class StudentsTable extends AppTable {
	public $InstitutionStudent;

	public function initialize(array $config) {
		$this->table('security_users');
		$this->entityClass('User.User');
		parent::initialize($config);

		// Associations
		BaseUsers::handleAssociations($this);
		self::handleAssociations($this);

		// Behaviors
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
			'fieldClass' => ['className' => 'StudentCustomField.StudentCustomFields'],
			'formKey' => 'student_custom_form_id',
			'filterKey' => 'student_custom_filter_id',
			'formFieldClass' => ['className' => 'StudentCustomField.StudentCustomFormsFields'],
			'formFilterClass' => ['className' => 'StudentCustomField.StudentCustomFormsFilters'],
			'recordKey' => 'student_id',
			'fieldValueClass' => ['className' => 'StudentCustomField.StudentCustomFieldValues', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => ['className' => 'StudentCustomField.StudentCustomTableCells', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true]
		]);

		$this->addBehavior('Excel', [
			'excludes' => ['photo_name', 'is_student', 'is_staff', 'is_guardian', 'super_admin', 'date_of_death'],
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
        $this->addBehavior('Import.ImportLink');

		$this->addBehavior('TrackActivity', ['target' => 'User.UserActivities', 'key' => 'security_user_id', 'session' => 'Student.Students.id']);

		$this->InstitutionStudent = TableRegistry::get('Institution.Students');
	}

	public static function handleAssociations($model) {

		// remove all student records from institution_students, institution_site_student_absences, student_behaviours, assessment_item_results, student_guardians, institution_student_admission, student_custom_field_values, student_custom_table_cells, student_fees, student_extracurriculars


		$model->belongsToMany('Institutions', [
			'className' => 'Institution.Institutions',
			'joinTable' => 'institution_students',
			'foreignKey' => 'student_id',
			'targetForeignKey' => 'institution_id',
			'through' => 'Institution.Students',
			'dependent' => true
		]);

        $model->hasMany('InstitutionStudents', ['className' => 'Institution.Students',    'foreignKey' => 'student_id', 'dependent' => true]);
		$model->hasMany('StudentAbsences', ['className' => 'Institution.InstitutionSiteStudentAbsences',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('StudentBehaviours', ['className' => 'Institution.StudentBehaviours',	'foreignKey' => 'student_id', 'dependent' => true]);
		$model->hasMany('AssessmentItemResults', ['className' => 'Assessment.AssessmentItemResults',	'foreignKey' => 'student_id', 'dependent' => true]);
		$model->belongsToMany('Guardians', [
			'className' => 'Student.Guardians',
			'foreignKey' => 'student_id',
			'targetForeignKey' => 'guardian_id',
			'through' => 'Student.StudentGuardians',
			'dependent' => true
		]);
		$model->hasMany('StudentAdmission', ['className' => 'Institution.StudentAdmission',	'foreignKey' => 'student_id', 'dependent' => true]);
		$model->hasMany('StudentCustomFieldValues', ['className' => 'CustomField.StudentCustomFieldValues',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('StudentCustomTableCells', ['className' => 'CustomField.StudentCustomTableCells',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('StudentFees', ['className' => 'Institution.StudentFeesAbstract',	'foreignKey' => 'student_id', 'dependent' => true]);
		$model->hasMany('Extracurriculars', ['className' => 'Student.Extracurriculars',	'foreignKey' => 'security_user_id', 'dependent' => true]);
	}

	public function validationDefault(Validator $validator) {
		$BaseUsers = TableRegistry::get('User.Users');
		return $BaseUsers->setUserValidation($validator, $this);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		// to set the student name in headers
		$this->Session->write('Student.Students.name', $entity->name);
		$this->request->data[$this->alias()]['student_id'] = $entity->id;
		$this->setupTabElements(['id' => $entity->id]);
	}

	public function indexBeforeAction(Event $event, ArrayObject $settings) {
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
			$this->joinInstitutionStudents($institutionIds, $query);
			$query->group([$this->aliasField('id')]);
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
		$this->ControllerAction->field('password', ['order' => 71]);
		$this->ControllerAction->field('is_student', ['value' => 1]);
	}

	public function addAfterAction(Event $event) {
		// need to find out order values because recordbehavior changes it
		$allOrderValues = [];
		foreach ($this->fields as $key => $value) {
			$allOrderValues[] = (array_key_exists('order', $value) && !empty($value['order']))? $value['order']: 0;
		}
		$highestOrder = max($allOrderValues);

		// username and password is always last...
		$this->ControllerAction->field('username', ['order' => ++$highestOrder, 'visible' => false]);
		$this->ControllerAction->field('password', ['order' => ++$highestOrder, 'visible' => false, 'type' => 'password', 'attr' => ['value' => '', 'autocomplete' => 'off']]);
	}

    // this function is no longer required because there should not be a ('Student.Students')->delete() after the implementation of directory
	// public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
	// 	$process = function($model, $id, $options) {
	// 		$studentData = $model->find()->where([$model->aliasField('id') => $id])->first();
	// 		// contain was used to test newly created associations
	// 		// $studentData->contain(['StudentAbsences', 'StudentBehaviours', 'AssessmentItemResults', 'Guardians', 'StudentAdmission', 'StudentCustomFieldValues', 'StudentCustomTableCells', 'StudentFees', 'Extracurriculars']);

	// 		if ($studentData) {
	// 			if ($studentData->is_staff || $studentData->is_guardian) {
	// 				// remove all student records from institution_students, institution_site_student_absences, student_behaviours, assessment_item_results, student_guardians, institution_student_admission, student_custom_field_values, student_custom_table_cells, student_fees, student_extracurriculars
	// 				$deletionInformation = [
	// 					['class' => 'Institution.Students', 'foreignKey' => 'student_id'],
	// 					['class' => 'Institution.InstitutionStudentAbsences', 'foreignKey' => 'student_id'],
	// 					['class' => 'Institution.StudentBehaviours', 'foreignKey' => 'student_id'],
	// 					['class' => 'Assessment.AssessmentItemResults', 'foreignKey' => 'student_id'],
	// 					['class' => 'Institution.StudentAdmission', 'foreignKey' => 'student_id'],
	// 					['class' => 'CustomField.StudentCustomFieldValues', 'foreignKey' => 'student_id'],
	// 					['class' => 'CustomField.StudentCustomTableCells', 'foreignKey' => 'student_id'],
	// 					['class' => 'Institution.StudentFeesAbstract', 'foreignKey' => 'student_id'],
	// 					['class' => 'Student.Extracurriculars', 'foreignKey' => 'security_user_id'],
	// 					['class' => 'Student.Guardians', 'foreignKey' => 'student_id'],
	// 				];

	// 				foreach ($deletionInformation as $key => $value) {
	// 					$deletionClass = TableRegistry::get($value['class']);
	// 					$deletionEntities = $deletionClass->find()->where([$deletionClass->aliasField($value['foreignKey']) => $id]);
	// 					foreach ($deletionEntities as $dkey => $dvalue) {
	// 						$deletionClass->delete($dvalue);
	// 					}
	// 				}

	// 				// do not delete user record
	// 				$model->updateAll(['is_student' => 0], [$model->primaryKey() => $id]);
	// 			} else {
	// 				// student is neither a guardian or staff... delete the user record along with all associated data
	// 				$model->delete($studentData);
	// 			}
	// 		}

	// 		// die('dead');

	// 		return true;
	// 	};
	// 	return $process;
	// }

	// Logic for the mini dashboard
	public function afterAction(Event $event) {
		if ($this->action == 'index') {

			$searchConditions = $this->getSearchConditions($this, $this->request->data['Search']['searchField']);
			$searchConditions['OR'] = array_merge($searchConditions['OR'], $this->advanceNameSearch($this, $this->request->data['Search']['searchField']));
			// Get total number of students
			$count = $this->find()
				->where([$this->aliasField('is_student') => 1])
				->where($searchConditions);

			if (!$this->AccessControl->isAdmin()) {
				$institutionIds = $this->Session->read('AccessControl.Institutions.ids');
				$this->joinInstitutionStudents($institutionIds, $count);
				$count->group([$this->aliasField('id')]);
			}

			$this->advancedSearchQuery($this->request, $count);

			// Get the gender for all students
			$data = [];
			$data[__('Gender')] = $this->getDonutChart('count_by_gender', ['searchConditions' => $searchConditions,'key' => __('Gender')]);

			$indexDashboard = 'dashboard';
			$this->controller->viewVars['indexElements']['mini_dashboard'] = [
	            'name' => $indexDashboard,
	            'data' => [
	            	'model' => 'students',
	            	'modelCount' => $count->count(),
	            	'modelArray' => $data,
	            ],
	            'options' => [],
	            'order' => 1
	        ];
	    }
	}

	private function joinInstitutionStudents(array $institutionIds, Query $query) {
		$query->innerJoin(
			['InstitutionStudent' => 'institution_students'],
			[
				'InstitutionStudent.student_id = ' . $this->aliasField($this->primaryKey()),
				'InstitutionStudent.institution_id IN ' => $institutionIds
			]
		);
	}

	private function setupTabElements($options) {
		$this->controller->set('selectedAction', $this->alias);
		$this->controller->set('tabElements', $this->controller->getUserTabElements($options));
	}

	// Function use by the mini dashboard (For Student.Students)
	public function getNumberOfStudentsByGender($params=[]) {
		$searchConditions = isset($params['searchConditions']) ? $params['searchConditions'] : [];
		$query = $this->find();
		$query
		->select(['gender_id', 'count' => $query->func()->count('DISTINCT '.$this->aliasField($this->primaryKey()))])
		->where([$this->aliasField('is_student') => 1])
		->where($searchConditions)
		->group('gender_id')
		;
		if (!$this->AccessControl->isAdmin()) {
			$institutionIds = $this->Session->read('AccessControl.Institutions.ids');
			$this->joinInstitutionStudents($institutionIds, $query);
		}

		$this->advancedSearchQuery($this->request, $query);

		$genders = $this->Genders->getList()->toArray();

		$resultSet = $query->all();
		$dataSet = [];
		foreach ($resultSet as $entity) {
			$dataSet[] = [__($genders[$entity['gender_id']]), $entity['count']];
		}
		$params['dataSet'] = $dataSet;
		return $params;
	}

	public function getAcademicTabElements($options = []) {
		// $action = (array_key_exists('action', $options))? $options['action']: 'add';
		$id = (array_key_exists('id', $options))? $options['id']: 0;

		$tabElements = [];
		$studentUrl = ['plugin' => 'Student', 'controller' => 'Students'];
		$studentTabElements = [
			'Programmes' => ['text' => __('Programmes')],
			'Classes' => ['text' => __('Classes')],
			'Subjects' => ['text' => __('Subjects')],
			'Absences' => ['text' => __('Absences')],
			'Behaviours' => ['text' => __('Behaviours')],
			'Results' => ['text' => __('Results')],
			'Awards' => ['text' => __('Awards')],
			'Extracurriculars' => ['text' => __('Extracurriculars')],
		];

		$tabElements = array_merge($tabElements, $studentTabElements);

		foreach ($studentTabElements as $key => $tab) {
			$tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>$key, 'index']);
		}
		return $tabElements;
	}

    public function findStudents(Query $query, array $options = []) {
        $query->where([$this->aliasField('is_student') => 1]);

        $limit = (array_key_exists('limit', $options))? $options['limit']: null;
        $page = (array_key_exists('page', $options))? $options['page']: null;

        // conditions
        $firstName = (array_key_exists('first_name', $options))? $options['first_name']: null;
        $lastName = (array_key_exists('last_name', $options))? $options['last_name']: null;
        $openemisNo = (array_key_exists('openemis_no', $options))? $options['openemis_no']: null;
        $identityNumber = (array_key_exists('identity_number', $options))? $options['identity_number']: null;

        $conditions = [];
        if (!empty($firstName)) $conditions['first_name LIKE'] = '%' . $firstName . '%';
        if (!empty($lastName)) $conditions['last_name LIKE'] = '%' . $lastName . '%';
        if (!empty($openemisNo)) $conditions['openemis_no LIKE'] = '%' . $openemisNo . '%';

        $identityConditions = [];
        if (!empty($identityNumber)) $identityConditions['Identities.number LIKE'] = '%' . $identityNumber . '%';

        $identityJoinType = (empty($identityNumber))? 'LEFT': 'INNER';
        $default_identity_type = $this->Identities->IdentityTypes->getDefaultValue();
        $query->join([
            [
                'type' => $identityJoinType,
                'table' => 'user_identities',
                'alias' => 'Identities',
                'conditions' => array_merge([
                        'Identities.security_user_id = ' . $this->aliasField('id'),
                        'Identities.identity_type_id' => $default_identity_type
                    ], $identityConditions)
            ]
        ]);

        // getting only enrolled records
        $query->contain([
            'InstitutionStudents.AcademicPeriods',
            'InstitutionStudents.Institutions',
            'InstitutionStudents.EducationGrades'
            ]
            , function($q) {
            return $q->where(['InstitutionStudents.student_status_id' => 1]);
        });

        if (!empty($conditions)) $query->where($conditions);
        if (!is_null($limit)) $query->limit($limit);
        if (!is_null($page)) $query->page($page);

        return $query;
    }
}
