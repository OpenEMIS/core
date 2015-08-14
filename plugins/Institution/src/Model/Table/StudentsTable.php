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
use App\Model\Table\AppTable;
use Security\Model\Table\SecurityUserTypesTable as UserTypes;
use Cake\I18n\Time;

class StudentsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_students');
		parent::initialize($config);

		$this->belongsTo('Users',			['className' => 'Security.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('StudentStatuses',	['className' => 'Student.StudentStatuses']);
		$this->belongsTo('EducationGrades',	['className' => 'Education.EducationGrades']);
		$this->belongsTo('Institutions',	['className' => 'Institution.InstitutionSites', 'foreignKey' => 'institution_id']);
		$this->belongsTo('AcademicPeriods',	['className' => 'AcademicPeriod.AcademicPeriods']);

		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
		$this->addBehavior('AcademicPeriod.Period');
		// to handle field type (autocomplete)
		$this->addBehavior('OpenEmis.Autocomplete');
		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');

		// $this->addBehavior('Student.Student');
		// $this->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' =>['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		// $this->addBehavior('Institution.User', ['associatedModel' => $this->InstitutionSiteStudents]);
		// $this->addBehavior('AdvanceSearch');
		$this->addBehavior('HighChart', [
			'number_of_students_by_year' => [
        		'_function' => 'getNumberOfStudentsByYear',
				'chart' => ['type' => 'column', 'borderWidth' => 1],
				'xAxis' => ['title' => ['text' => 'Years']],
				'yAxis' => ['title' => ['text' => 'Total']]
			],
			'number_of_students_by_grade' => [
        		'_function' => 'getNumberOfStudentsByGrade',
				'chart' => ['type' => 'column', 'borderWidth' => 1],
				'xAxis' => ['title' => ['text' => 'Education']],
				'yAxis' => ['title' => ['text' => 'Total']]
			],
			'institution_student_gender' => [
				'_function' => 'getNumberOfStudentsByGender'
			],
			'institution_student_age' => [
				'_function' => 'getNumberOfStudentsByAge'
			],
			'institution_site_section_student_grade' => [
        		'_function' => 'getNumberOfStudentsByGradeByInstitution'
			]
		]);
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	public function beforeAction(Event $event) {
		$institutionId = $this->Session->read('Institutions.id');
		$this->ControllerAction->field('institution_id', ['type' => 'hidden', 'value' => $institutionId]);
		$this->ControllerAction->field('student_status_id', ['type' => 'select']);
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->ControllerAction->field('academic_period_id', ['visible' => false]);
		$this->ControllerAction->field('class', ['order' => 90]);
		$this->ControllerAction->field('student_status_id', ['order' => 100]);
		$this->fields['start_date']['visible'] = false;
		$this->fields['end_date']['visible'] = false;
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->contain(['EducationGrades']);

		// Student Statuses
		$statusOptions = $this->StudentStatuses
			->find('list')
			->toArray();

		// Academic Periods
		//$academicPeriodOptions = $this->AcademicPeriods->getList();
		$academicPeriodOptions = $this->AcademicPeriods->getListWithLevels();

		// Education Grades
		$institutionEducationGrades = TableRegistry::get('Institution.InstitutionSiteGrades');
		$session = $this->Session;
		$institutionId = $session->read('Institutions.id');
		$educationGradesOptions = $institutionEducationGrades
			->find('list', [
					'keyField' => 'EducationGrades.id',
					'valueField' => 'EducationGrades.name'
				])
			->select([
					'EducationGrades.id', 'EducationGrades.name'
				])
			->contain(['EducationGrades'])
			->where(['institution_site_id' => $institutionId])
			->group('education_grade_id')
			->toArray();
		$addGradesOption = ['-1' => __('All Grades')];
		$educationGradesOptions = $addGradesOption + $educationGradesOptions;

		$statusOptions = ['-1' => __('All Statuses')] + $statusOptions;
		// Query Strings
		$selectedStatus = $this->queryString('status_id', $statusOptions);
		//$selectedAcademicPeriod = $this->queryString('academic_period_id', $academicPeriodOptions);
		$selectedAcademicPeriod = $request->query('academic_period_id');
		if (empty($selectedAcademicPeriod)) {
			$selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
			$request->query['academic_period_id'] = $selectedAcademicPeriod;
		}
		$selectedEducationGrades = $this->queryString('education_grade_id', $educationGradesOptions);

		// Advanced Select Options
		$this->advancedSelectOptions($statusOptions, $selectedStatus);
		//$this->advancedSelectOptions($academicPeriodOptions, $selectedAcademicPeriod);
		$this->advancedSelectOptionsForLevels($academicPeriodOptions, $selectedAcademicPeriod);
		$this->advancedSelectOptions($educationGradesOptions, $selectedEducationGrades);

		if ($selectedEducationGrades != -1) {
			$query->where([$this->aliasField('education_grade_id') => $selectedEducationGrades]);
		}

		if ($selectedStatus != -1) {
			$query->where([$this->aliasField('student_status_id') => $selectedStatus]);
		}

		$query->where([$this->aliasField('academic_period_id') => $selectedAcademicPeriod]);

		$search = $this->ControllerAction->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->addSearchConditions($query, ['searchTerm' => $search]);
		}

		$this->controller->set(compact('statusOptions', 'academicPeriodOptions', 'educationGradesOptions'));
		// End
	}

	public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$errors = $entity->errors();
		if (!empty($errors)) {
			$entity->unsetProperty('student_id');
			unset($data[$this->alias()]['student_id']);
		}
	}

	public function onGetStudentId(Event $event, Entity $entity) {
		$value = '';
		if ($entity->has('user')) {
			$value = $entity->user->name;
		} else {
			$value = $entity->_matchingData['Users']->name;
		}
		return $value;
	}

	public function onGetEducationGradeId(Event $event, Entity $entity) {
		$value = '';
		if ($entity->has('education_grade')) {
			$value = $entity->education_grade->programme_grade_name;
		}
		return $value;
	}

	public function onGetClass(Event $event, Entity $entity) {
		$value = '';
		$academicPeriodId = $entity->academic_period_id;
		$studentId = $entity->_matchingData['Users']->id;
		$educationGradeId = $entity->education_grade->id;
		$institutionId = $entity->institution_id;

		$ClassStudents = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
		$class = $ClassStudents->find()
		->select(['class.name'])
		->innerJoin(
			['class' => 'institution_site_sections'],
			[
				'class.id = ' . $ClassStudents->aliasField('institution_site_section_id'),
				'class.academic_period_id' => $academicPeriodId,
				'class.institution_site_id' => $institutionId
			]
		)
		->where([
			$ClassStudents->aliasField('student_id') => $studentId,
			$ClassStudents->aliasField('education_grade_id') => $educationGradeId,
			$ClassStudents->aliasField('status') => 1
		])
		->first();

		if ($class) {
			$value = $class->class['name'];
		}
		return $value;
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->field('student_id');

		if ($this->action == 'index') {
			$institutionSiteArray = [];
			$session = $this->Session;
			$institutionId = $session->read('Institutions.id');

			// Get number of student in institution
			$periodId = $this->request->query('academic_period_id');
			$studentCount = $this->find()
				->where([
					$this->aliasField('institution_id') => $institutionId,
					$this->aliasField('academic_period_id') => $periodId
				])
				->group(['student_id'])
				->count();

			//Get Gender
			$institutionSiteArray['Gender'] = $this->getDonutChart('institution_student_gender', 
				['institution_id' => $institutionId, 'academic_period_id' => $periodId, 'key' => 'Gender']);
			
			// Get Age
			$institutionSiteArray['Age'] = $this->getDonutChart('institution_student_age', 
				['institution_id' => $institutionId, 'academic_period_id' => $periodId, 'key' => 'Age']);

			// Get Grades
			$institutionSiteArray['Grade'] = $this->getDonutChart('institution_site_section_student_grade', 
				['institution_id' => $institutionId, 'academic_period_id' => $periodId, 'key'=>'Grade']);


			$indexDashboard = 'dashboard';
			$indexElements = $this->controller->viewVars['indexElements'];
			
			$indexElements[] = ['name' => 'Institution.Students/controls', 'data' => [], 'options' => [], 'order' => 2];
			
			$indexElements[] = [
	            'name' => $indexDashboard,
	            'data' => [
	            	'model' => 'students',
	            	'modelCount' => $studentCount,
	            	'modelArray' => $institutionSiteArray,
	            ],
	            'options' => [],
	            'order' => 1
	        ];
	        $this->controller->set('indexElements', $indexElements);
	    }
	}

	public function addAfterAction(Event $event, Entity $entity) {
		list($periodOptions, $selectedPeriod, $gradeOptions, $selectedGrade, $sectionOptions, $selectedSection) = array_values($this->_getSelectOptions());

		$this->ControllerAction->field('academic_period_id', ['options' => $periodOptions]);
		$this->ControllerAction->field('education_grade_id', ['options' => $gradeOptions]);
		$this->ControllerAction->field('class', ['options' => $sectionOptions]);

		if ($selectedPeriod != 0) {
			$period = $this->AcademicPeriods->get($selectedPeriod);
		} else {
			$period = $this->AcademicPeriods->get(key($periodOptions));
		}

		$this->ControllerAction->field('id', ['value' => Text::uuid()]);
		$this->ControllerAction->field('start_date', ['period' => $period]);
		$this->ControllerAction->field('end_date', ['period' => $period]);

		$this->ControllerAction->setFieldOrder([
			'academic_period_id', 'education_grade_id', 'class', 'student_status_id', 'start_date', 'end_date', 'student_id'
		]);

		$this->setupTabElements($entity);
	}

	public function addAfterSave(Event $event, Entity $entity, ArrayObject $data) {
		if ($entity->class > 0) {
			$sectionData = [];
			$sectionData['student_id'] = $entity->student_id;
			$sectionData['education_grade_id'] = $entity->education_grade_id;
			$sectionData['institution_site_section_id'] = $entity->class;
			$InstitutionSiteSectionStudents = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
			$InstitutionSiteSectionStudents->autoInsertSectionStudent($sectionData);
		}
	}

	public function viewBeforeAction(Event $event) {
		$this->ControllerAction->field('photo_content', ['type' => 'image', 'order' => 0]);
		$this->ControllerAction->field('openemis_no', ['type' => 'readonly', 'order' => 1]);
		$this->fields['student_id']['order'] = 10;
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupTabElements($entity);
	}

	private function setupTabElements($entity) {
		$url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name];

		$tabElements = [
			'Students' => ['text' => __('Academic')],
			'StudentUser' => ['text' => __('General')]
		];

		if ($this->action == 'add') {
			$tabElements['Students']['url'] = array_merge($url, ['action' => $this->alias(), 'add']);
			$tabElements['StudentUser']['url'] = array_merge($url, ['action' => 'StudentUser', 'add']);
		} else {
			$tabElements['Students']['url'] = array_merge($url, ['action' => $this->alias(), 'view', $entity->id]);
			$tabElements['StudentUser']['url'] = array_merge($url, ['action' => 'StudentUser', 'view', $entity->student_id, 'id' => $entity->id]);
		}

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function editBeforeQuery(Event $event, Query $query) {
		$query->contain(['Users', 'EducationGrades', 'AcademicPeriods', 'StudentStatuses']);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('student_id', [
			'type' => 'readonly', 
			'order' => 10, 
			'attr' => ['value' => $entity->user->name_with_id]
		]);
		$this->ControllerAction->field('education_grade_id', ['type' => 'readonly', 'attr' => ['value' => $entity->education_grade->programme_grade_name]]);
		$this->ControllerAction->field('academic_period_id', ['type' => 'readonly', 'attr' => ['value' => $entity->academic_period->name]]);
		$this->ControllerAction->field('student_status_id', ['type' => 'readonly', 'attr' => ['value' => $entity->student_status->name]]);
		$period = $entity->academic_period;
		$endDate = $period->end_date->copy();
		$this->fields['start_date']['date_options'] = ['startDate' => $period->start_date->format('d-m-Y')];
		$this->fields['end_date']['date_options'] = ['endDate' => $endDate->subDay()->format('d-m-Y')];
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['onChangeReload'] = 'changePeriod';
		}
		return $attr;
	}

	public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request) {
		$attr['onChangeReload'] = 'changeEducationGrade';
		return $attr;
	}

	public function onUpdateFieldStartDate(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$period = $attr['period'];
			$endDate = $period->end_date->copy();
			$attr['date_options']['startDate'] = $period->start_date->format('d-m-Y');
			$attr['date_options']['endDate'] = $endDate->subDay()->format('d-m-Y');
			$attr['value'] = $period->start_date->format('d-m-Y');
			$attr['default_date'] = false;
		}
		return $attr;
	}

	public function onUpdateFieldEndDate(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$period = $attr['period'];
			$attr['type'] = 'readonly';
			$attr['attr'] = ['value' => $period->end_date->format('d-m-Y')];
			$attr['value'] = $period->end_date->format('Y-m-d');
		}
		return $attr;
	}

	public function onUpdateFieldStudentId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['type'] = 'autocomplete';
			$attr['target'] = ['key' => 'student_id', 'name' => $this->aliasField('student_id')];
			$attr['noResults'] = $this->getMessage($this->aliasField('noStudents'));
			$attr['attr'] = ['placeholder' => __('OpenEMIS ID or Name')];
			$attr['url'] = ['controller' => 'Institutions', 'action' => 'Students', 'ajaxUserAutocomplete'];

			$iconSave = '<i class="fa fa-check"></i> ' . __('Save');
			$iconAdd = '<i class="fa kd-add"></i> ' . __('Create New');
			$attr['onNoResults'] = "$('.btn-save').html('" . $iconAdd . "').val('new')";
			$attr['onBeforeSearch'] = "$('.btn-save').html('" . $iconSave . "').val('save')";
		} else if ($action == 'index') {
			$attr['sort'] = ['field' => 'Users.first_name'];
		}
		return $attr;
	}

	public function addOnChangePeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['period']);
		unset($request->query['grade']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
					$request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
				}
			}
		}
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		$this->Session->delete('Institutions.Students.new');
	}

	public function addOnNew(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$this->Session->write('Institutions.Students.new', $data[$this->alias()]);
		$event->stopPropagation();
		$action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'StudentUser', 'add'];
		return $this->controller->redirect($action);
	}

	public function addOnChangeEducationGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['period']);
		unset($request->query['grade']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
					$request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
				}
				if (array_key_exists('education_grade_id', $request->data[$this->alias()])) {
					$request->query['grade'] = $request->data[$this->alias()]['education_grade_id'];
				}
			}
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
			
			// only search for students
			$query->where([$UserTypes->aliasField('user_type') => UserTypes::STUDENT]);
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

	public function validationDefault(Validator $validator) {
		return $validator
			->add('start_date', 'ruleCompareDate', [
				'rule' => ['compareDate', 'end_date', false]
			])
			->add('end_date', [
			])
			->add('student_status_id', [
			])
			->add('academic_period_id', [
			])
			// ->allowEmpty('student_id') required for create new but disabling for now
			->add('student_id', 'ruleInstitutionStudentId', [
				'rule' => ['institutionStudentId'],
				'on' => 'create'
			])
		;
	}

	public function _getSelectOptions() {
		//Return all required options and their key
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$Grades = TableRegistry::get('Institution.InstitutionSiteGrades');
		$InstitutionSiteSections = TableRegistry::get('Institution.InstitutionSiteSections');
		$institutionId = $this->Session->read('Institutions.id');

		// Academic Period
		$periodOptions = $AcademicPeriod->getList();

		$selectedPeriod = !is_null($this->request->query('period')) ? $this->request->query('period') : key($periodOptions);
		// $this->advancedSelectOptions($periodOptions, $selectedPeriod, []);
		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
			'callable' => function($id) use ($Grades, $institutionId) {
				return $Grades
					->find()
					->where([$Grades->aliasField('institution_site_id') => $institutionId])
					->find('academicPeriod', ['academic_period_id' => $id])
					->count();
			}
		]);
		// End

		// Grade
		$gradeOptions = [];
		$sectionOptions = ['0' => __('-- Select Class -- ')];
		$selectedSection = 0;

		if ($selectedPeriod != 0) {
			$data = $Grades->find()
			->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
			->contain('EducationGrades.EducationProgrammes')
			->where([$Grades->aliasField('institution_site_id') => $institutionId])
			->all();

			foreach ($data as $entity) {
				$gradeOptions[$entity->education_grade->id] = $entity->education_grade->programme_grade_name;
			}

			$selectedGrade = !is_null($this->request->query('grade')) ? $this->request->query('grade') : key($gradeOptions);
			$this->advancedSelectOptions($gradeOptions, $selectedGrade, []);
			// End
			
			// section
			$sectionOptions = $sectionOptions + $InstitutionSiteSections->getSectionOptions($selectedPeriod, $institutionId, $selectedGrade);
			// End
		}
		
		return compact('periodOptions', 'selectedPeriod', 'gradeOptions', 'selectedGrade', 'sectionOptions', 'selectedSection');
	}


    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
    	if ($action == 'view') {
    		if ($this->AccessControl->check([$this->controller->name, 'TransferRequests', 'add'])) {
				$TransferRequests = TableRegistry::get('Institution.TransferRequests');
				$StudentPromotion = TableRegistry::get('Institution.StudentPromotion');
	    		$StudentStatuses = TableRegistry::get('Student.StudentStatuses');

				$id = $this->request->params['pass'][1];
				$selectedStudent = $this->get($id)->student_id;
				$selectedPeriod = $this->get($id)->academic_period_id;
				$selectedGrade = $this->get($id)->education_grade_id;
	    		$this->Session->write($TransferRequests->alias().'.id', $id);

	    		// Show Transfer button only if the Student Status is Current
				$institutionId = $this->Session->read('Institutions.id');
				$currentStatus = $StudentStatuses
					->find()
					->where([$StudentStatuses->aliasField('code') => 'CURRENT'])
					->first()
					->id;
				$pendingStatus = $StudentStatuses
					->find()
					->where([$StudentStatuses->aliasField('code') => 'PENDING_TRANSFER'])
					->first()
					->id;

				$student = $StudentPromotion
					->find()
					->where([
						$StudentPromotion->aliasField('institution_id') => $institutionId,
						$StudentPromotion->aliasField('student_id') => $selectedStudent,
						$StudentPromotion->aliasField('academic_period_id') => $selectedPeriod,
						$StudentPromotion->aliasField('education_grade_id') => $selectedGrade
					])
					->first();
				// End

				// Transfer button
				$transferButton = $buttons['back'];
				$transferButton['type'] = 'button';
				$transferButton['label'] = '<i class="fa kd-transfer"></i>';
				$transferButton['attr'] = $attr;
				$transferButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
				$transferButton['attr']['title'] = __('Transfer');
				//End

	    		if ($student->student_status_id == $currentStatus) {
	    			$transferButton['url'] = [
			    		'plugin' => $buttons['back']['url']['plugin'],
			    		'controller' => $buttons['back']['url']['controller'],
			    		'action' => 'TransferRequests',
			    		'add'
			    	];
					$toolbarButtons['transfer'] = $transferButton;
				} else if ($student->student_status_id == $pendingStatus) {
					$transferRequest = $TransferRequests
						->find()
						->where([
							$TransferRequests->aliasField('previous_institution_id') => $institutionId,
							$TransferRequests->aliasField('security_user_id') => $selectedStudent,
							$TransferRequests->aliasField('status') => 0
						])
						->first();

					$transferButton['url'] = [
						'plugin' => $buttons['back']['url']['plugin'],
			    		'controller' => $buttons['back']['url']['controller'],
			    		'action' => 'TransferRequests',
			    		'edit',
			    		$transferRequest->id
			    	];
			    	$toolbarButtons['transfer'] = $transferButton;
				}
			}
		}
	}

	// Function use by the mini dashboard (For Institution Students)
	public function getNumberOfStudentsByGender($params=[]) {

		$institutionSiteRecords = $this->find();
		$institutionSiteStudentCount = $institutionSiteRecords
			->contain(['Users', 'Users.Genders'])
			->select([
				'count' => $institutionSiteRecords->func()->count('DISTINCT student_id'),	
				'gender' => 'Genders.name'
			])
			->group('gender');

		if (!empty($params['institution_id'])) {
			$institutionSiteStudentCount->where(['institution_id' => $params['institution_id']]);
		}

		if (!empty($params['academic_period_id'])) {
			$institutionSiteStudentCount->where(['academic_period_id' => $params['academic_period_id']]);
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

	// Function use by the mini dashboard (For Institution Students)
	public function getNumberOfStudentsByAge($params=[]) {

		$studentsConditions = [
			'Users.date_of_death IS NULL',
		];

		if (!empty($params['institution_id'])) {
			$studentsConditions = array_merge($studentsConditions, ['institution_id' => $params['institution_id']]);
		}

		if (!empty($params['academic_period_id'])) {
			$studentsConditions = array_merge($studentsConditions, ['academic_period_id' => $params['academic_period_id']]);
		}

		$today = Time::today();

		$institutionSiteRecords = $this->find();
		$query = $institutionSiteRecords
			->contain(['Users'])
			->select([
				'age' => $institutionSiteRecords->func()->dateDiff([
					$institutionSiteRecords->func()->now(),
					'Users.date_of_birth' => 'literal'
				])
			])
			->where($studentsConditions)
			->order('age');

		$institutionSiteStudentCount = $query->toArray();

		$convertAge = [];
		
		// (Logic to be reviewed)
		// Calculate the age taking account to the average of leap years 
		foreach($institutionSiteStudentCount as $val){
			$convertAge[] = floor($val['age']/365.25);
		}
		// Count and sort the age
		$result = [];
		$prevValue = ['age' => -1, 'count' => null];
		foreach ($convertAge as $val) {
	    	if ($prevValue['age'] != $val) {
	        	unset($prevValue);
	        	$prevValue = ['age' => $val, 'count' => 0];
	        	$result[] =& $prevValue;
	    	}
    		$prevValue['count']++;
		}
		
		// Creating the data set		
		$dataSet = [];
		foreach ($result as $value) {
            //Compile the dataset
			$dataSet[] = ['Age '.$value['age'], $value['count']];
		}
		$params['dataSet'] = $dataSet;
		return $params;
	}

	// Function use by the mini dashboard (For Institution Students)
	public function getNumberOfStudentsByGradeByInstitution($params=[]) {
		$studentsByGradeConditions = [
			$this->aliasField('student_status_id') => 1,
			$this->aliasField('education_grade_id').' IS NOT NULL',
		];

		if (!empty($params['institution_id'])) {
			$studentsByGradeConditions = array_merge($studentsByGradeConditions, ['institution_id' => $params['institution_id']]);

		}

		if (!empty($params['academic_period_id'])) {
			$studentsByGradeConditions = array_merge($studentsByGradeConditions, ['academic_period_id' => $params['academic_period_id']]);
		}

		$query = $this->find();
		$studentByGrades = $query
			->select([
				'grade' => 'EducationGrades.name',
				'count' => $query->func()->count($this->aliasField('student_id'))
			])
			->contain([
				'EducationGrades'
			])
			->where($studentsByGradeConditions)
			->group([
				$this->aliasField('education_grade_id'),
			])
			->toArray();

		$dataSet = [];
		foreach($studentByGrades as $value){
			$dataSet[] = [$value['grade'], $value['count']];
		}
		$params['dataSet'] = $dataSet;

		return $params;
	}

	// For Dashboard (Institution Dashboard and Home Page)
	public function getNumberOfStudentsByYear($params=[]) {
		$conditions = isset($params['conditions']) ? $params['conditions'] : [];
		$_conditions = [];
		foreach ($conditions as $key => $value) {
			$_conditions[$this->alias().'.'.$key] = $value;
		}

		$periodConditions = $_conditions;
		$query = $this->find();
		$periodResult = $query
			->select([
				'min_year' => $query->func()->min($this->aliasField('start_year')),
				'max_year' => $query->func()->max($this->aliasField('end_year'))
			])
			->where($periodConditions)
			->first();
		$AcademicPeriod = $this->AcademicPeriods;
		$currentPeriodId = $AcademicPeriod->getCurrent();
		$currentPeriodObj = $AcademicPeriod->get($currentPeriodId);
		$thisYear = $currentPeriodObj->end_year;
		$minYear = $thisYear - 2;
		$minYear = $minYear > $periodResult->min_year ? $minYear : $periodResult->min_year;
		$maxYear = $thisYear;

		$years = [];

		$genderOptions = $this->Users->Genders->getList();
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
					$this->aliasField('end_year').' IS NOT NULL',
					$this->aliasField('start_year').' <= "' . $currentYear . '"',
					$this->aliasField('end_year').' >= "' . $currentYear . '"'
				]
			];

			$query = $this->find();
			$studentsByYear = $query
				->contain(['Users.Genders'])
				->select([
					'Users.first_name',
					'Genders.name',
					'total' => $query->func()->count($this->aliasField('id'))
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

	// For Dashboard (Home Page and Institution Dashboard page)
	public function getNumberOfStudentsByGrade($params=[]) {
		$conditions = isset($params['conditions']) ? $params['conditions'] : [];
		$_conditions = [];
		foreach ($conditions as $key => $value) {
			$_conditions[$this->alias().'.'.$key] = $value;
		}

		$AcademicPeriod = $this->AcademicPeriods;
		$currentYearId = $AcademicPeriod->getCurrent();
		$currentYear = $AcademicPeriod->get($currentYearId, ['fields'=>'name'])->name;

		$studentsByGradeConditions = [
			$this->aliasField('student_status_id') => 1,
			$this->aliasField('academic_period_id') => $currentYearId,
			$this->aliasField('education_grade_id').' IS NOT NULL',
			'Genders.name IS NOT NULL'
		];
		$studentsByGradeConditions = array_merge($studentsByGradeConditions, $_conditions);
		$query = $this->find();
		$studentByGrades = $query
			->select([
				$this->aliasField('institution_id'),
				$this->aliasField('education_grade_id'),
				'EducationGrades.name',
				'Users.id',
				'Genders.name',
				'total' => $query->func()->count($this->aliasField('id'))
			])
			->contain([
				'EducationGrades',
				'Users.Genders'
			])
			->where($studentsByGradeConditions)
			->group([
				$this->aliasField('education_grade_id'),
				'Genders.name'
			])
			->order(
				'EducationGrades.order',
				$this->aliasField('institution_id')
			)
			->toArray()
			;


		$grades = [];
		
		$genderOptions = $this->Users->Genders->getList();
		$dataSet = array();
		foreach ($genderOptions as $key => $value) {
			$dataSet[$value] = array('name' => __($value), 'data' => array());
		}

		foreach ($studentByGrades as $key => $studentByGrade) {
			$gradeId = $studentByGrade->education_grade_id;
			$gradeName = $studentByGrade->education_grade->name;
			$gradeGender = $studentByGrade->user->gender->name;
			$gradeTotal = $studentByGrade->total;

			$grades[$gradeId] = $gradeName;

			foreach ($dataSet as $dkey => $dvalue) {
				if (!array_key_exists($gradeId, $dataSet[$dkey]['data'])) {
					$dataSet[$dkey]['data'][$gradeId] = 0;
				}
			}
			$dataSet[$gradeGender]['data'][$gradeId] = $gradeTotal;
		}

		$params['options']['subtitle'] = array('text' => 'For Year '. $currentYear);
		$params['options']['xAxis']['categories'] = array_values($grades);
		$params['dataSet'] = $dataSet;

		return $params;
	}
}
