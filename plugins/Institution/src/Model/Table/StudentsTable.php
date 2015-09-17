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
use Cake\I18n\Time;
use App\Model\Table\AppTable;
use Cake\Utility\Inflector;

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
		$this->addBehavior('AcademicPeriod.AcademicPeriod');

		$this->addBehavior('Excel', [
			'excludes' => ['start_year', 'end_year'], 
			'pages' => ['index']
		]);

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
			->add('student_name', 'ruleInstitutionStudentId', [
				'rule' => ['institutionStudentId'],
				'on' => 'create'
			])
			->add('student_name', 'ruleStudentEnrolledInOthers', [
				'rule' => ['checkEnrolledInOtherInstitution'],
				'on' => 'create'
			])
		;
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$query->where([$this->aliasField('institution_id') => $institutionId]);
		$periodId = $this->request->query['academic_period_id'];
		if ($periodId > 0) {
			$query->where([$this->aliasField('academic_period_id') => $periodId]);
		}
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		return $events;
	}

	public function beforeAction(Event $event) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$this->ControllerAction->field('institution_id', ['type' => 'hidden', 'value' => $institutionId]);
		$this->ControllerAction->field('student_status_id', ['type' => 'select']);
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->ControllerAction->field('academic_period_id', ['visible' => false]);
		$this->ControllerAction->field('class', ['order' => 90]);
		$this->ControllerAction->field('student_status_id', ['order' => 100]);
		$this->fields['start_date']['visible'] = false;
		$this->fields['end_date']['visible'] = false;
		
		$StudentStatusesTable = $this->StudentStatuses;
		$status = $StudentStatusesTable->findCodeList();
		$selectedStatus = $this->request->query('status_id');
		switch ($selectedStatus) {
			case $status['PENDING_ADMISSION']:
			case $status['REJECTED']:
				$event->stopPropagation();
				return $this->controller->redirect(['plugin'=>'Institution', 'controller' => 'Institutions', 'action' => 'StudentAdmission']);
				break;
			case $status['PENDING_TRANSFER']:
				$event->stopPropagation();
				return $this->controller->redirect(['plugin'=>'Institution', 'controller' => 'Institutions', 'action' => 'TransferRequests']);
				break;
		}
	}

	 /**
     * Method to check if the student is enrolled in all institution within a particular
     * education system in the academic period
     *
     * @param integer $studentId The student identitifier (id)
     * @param integer $academicPeriodId The selected academic period id
     * @param integer $systemId The education system id to check
     * @return bool True if the student is enrolled in the same education system in that academic period
     */
	public function checkIfEnrolledInAllInstitution($studentId, $academicPeriodId, $systemId) {
		$EducationGradesTable = TableRegistry::get('Education.EducationGrades');
		$gradeIds = $this->find('list', [
					'keyField' => 'education_grade_id',
					'valueField' => 'education_grade_id'
				])
				->where([
					$this->aliasField('student_id') => $studentId, 
					$this->aliasField('academic_period_id') => $academicPeriodId,
					$this->aliasField('student_status_id') => $this->StudentStatuses->getIdByCode('CURRENT')
				])
				->toArray();

		$educationSystemId = [];
		foreach ($gradeIds as $grade) {
			$eduSystemId = $EducationGradesTable->getEducationSystemId($grade);
			if (!in_array($systemId, $educationSystemId)) {
				$educationSystemId[] = $eduSystemId;
			}
		}
		return in_array($systemId, $educationSystemId);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->contain(['EducationGrades']);

		// Student Statuses
		$statusOptions = $this->StudentStatuses
			->find('list')
			->toArray();

		// Academic Periods
		$academicPeriodOptions = $this->AcademicPeriods->getList();

		// Education Grades
		$InstitutionEducationGrades = TableRegistry::get('Institution.InstitutionSiteGrades');
		$session = $this->Session;
		$institutionId = $session->read('Institution.Institutions.id');
		$educationGradesOptions = $InstitutionEducationGrades
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
		
		$educationGradesOptions = ['-1' => __('All Grades')] + $educationGradesOptions;
		$statusOptions = ['-1' => __('All Statuses')] + $statusOptions;

		// Query Strings

		if (empty($request->query['academic_period_id'])) {
			$request->query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
		}
		$selectedStatus = $this->queryString('status_id', $statusOptions);
		$selectedEducationGrades = $this->queryString('education_grade_id', $educationGradesOptions);
		$selectedAcademicPeriod = $this->queryString('academic_period_id', $academicPeriodOptions);



		// Advanced Select Options
		$this->advancedSelectOptions($statusOptions, $selectedStatus);
		$studentTable = $this;
		$this->advancedSelectOptions($academicPeriodOptions, $selectedAcademicPeriod, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
			'callable' => function($id) use ($studentTable, $institutionId) {
				return $studentTable->find()->where(['institution_id'=>$institutionId, 'academic_period_id'=>$id])->count();
			}
		]);

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
			$query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
		}

		$this->controller->set(compact('statusOptions', 'academicPeriodOptions', 'educationGradesOptions'));
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

			$institutionId = $session->read('Institution.Institutions.id');
			// Get number of student in institution
			$periodId = $this->request->query['academic_period_id'];
			$statusId = $this->request->query['status_id'];
			$educationGradeId = $this->request->query['education_grade_id'];
			$conditions['institution_id'] = $institutionId;
			$conditions['academic_period_id'] = $periodId;

			$studentCount = $this->find()
				->where([
					$this->aliasField('institution_id') => $institutionId,
					$this->aliasField('academic_period_id') => $periodId
				])
				->group(['student_id']);
			
			if ($statusId != -1) {
				$studentCount->where([
					$this->aliasField('student_status_id') => $statusId
				]);
				$conditions['student_status_id'] = $statusId;
			}
			
			if ($educationGradeId != -1) {
				$studentCount->where([
					$this->aliasField('education_grade_id') => $educationGradeId
				]);
				$conditions['education_grade_id'] = $educationGradeId;
			}



			//Get Gender
			$institutionSiteArray[__('Gender')] = $this->getDonutChart('institution_student_gender', 
				['conditions' => $conditions, 'key' => __('Gender')]);
			
			// Get Age
			$institutionSiteArray[__('Age')] = $this->getDonutChart('institution_student_age', 
				['conditions' => $conditions, 'key' => __('Age')]);

			// Get Grades
			$institutionSiteArray[__('Grade')] = $this->getDonutChart('institution_site_section_student_grade', 
				['conditions' => $conditions, 'key' => __('Grade')]);


			$indexDashboard = 'dashboard';
			$indexElements = $this->controller->viewVars['indexElements'];
			
			$indexElements[] = ['name' => 'Institution.Students/controls', 'data' => [], 'options' => [], 'order' => 2];
			
			$indexElements[] = [
				'name' => $indexDashboard,
				'data' => [
					'model' => 'students',
					'modelCount' => $studentCount->count(),
					'modelArray' => $institutionSiteArray,
				],
				'options' => [],
				'order' => 1
			];
			$this->controller->set('indexElements', $indexElements);
		}
	}

	public function addAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('student_name');

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
			'academic_period_id', 'education_grade_id', 'class', 'student_status_id', 'start_date', 'end_date', 'student_name'
		]);
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		$buttons[0]['name'] = '<i class="fa kd-add"></i> ' . __('Create New');
		$buttons[0]['attr']['value'] = 'new';
	}

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		$AdmissionTable = TableRegistry::get('Institution.StudentAdmission');
		$studentData = $data['Students'];
		$studentExist = 0;
		$studentId = $entity->student_id;

		// Check if student has already been enrolled
		if (!empty ($studentId)) {

			$pendingAdmissionCode = $this->StudentStatuses->getIdByCode('PENDING_ADMISSION');
			$educationSystemId = TableRegistry::get('Education.EducationGrades')->getEducationSystemId($entity->education_grade_id);
			// Check if the student that is pass over is a pending admission student
			if ($pendingAdmissionCode == $studentData['student_status_id'] && 
				!$this->checkIfEnrolledInAllInstitution($studentId, $studentData['academic_period_id'], $educationSystemId)) {

				// Check if the student is a new record in the admission table, if the record exist as an approved record or rejected record, that record should
				// be retained for auditing purposes as the student may be approved in the first place, then remove from the institution for some reason, then added back
				$studentExist = $AdmissionTable->find()
					->where([
							$AdmissionTable->aliasField('status') => 0,
							$AdmissionTable->aliasField('student_id') => $studentId,
							$AdmissionTable->aliasField('institution_id') => $studentData['institution_id'],
							$AdmissionTable->aliasField('academic_period_id') => $studentData['academic_period_id'],
							$AdmissionTable->aliasField('education_grade_id') => $studentData['education_grade_id'],
							$AdmissionTable->aliasField('type') => 1
						])
					->count();
				// Check if the student is already added to the student admission table
				if ($studentExist == 0) {
					$process = function ($model, $entity) use ($studentData, $AdmissionTable, $studentId) {
						$admissionStatus = 1;
						$entityData = [
							'start_date' => $studentData['start_date'],
							'end_date' => $studentData['end_date'],
							'student_id' => $studentId,
							'status' => 0,
							'institution_id' => $studentData['institution_id'],
							'academic_period_id' => $studentData['academic_period_id'],
							'education_grade_id' => $studentData['education_grade_id'],
							'previous_institution_id' => 0,
							'student_transfer_reason_id' => 0,
							'type' => $admissionStatus,
						];

						$admissionEntity = $AdmissionTable->newEntity($entityData);
						if( $AdmissionTable->save($admissionEntity) ){
							return true;
						} else {
							$AdmissionTable->log($admissionEntity->errors(), 'debug');
							return false;
						}
					};
					return $process;
				} else {
					$process = function ($model, $entity){
						return false;
					};
					$this->Alert->error('StudentAdmission.existsInRecord');
					return $process;
				}			
			}
		}
	}

	public function addAfterSave(Event $event, Entity $entity, ArrayObject $data) {
		$StudentStatuses = TableRegistry::get('Student.StudentStatuses');
		if ($StudentStatuses->get($entity->student_status_id)->code == 'CURRENT') {
			if ($entity->class > 0) {
				$sectionData = [];
				$sectionData['student_id'] = $entity->student_id;
				$sectionData['education_grade_id'] = $entity->education_grade_id;
				$sectionData['institution_site_section_id'] = $entity->class;
				$InstitutionSiteSectionStudents = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
				$InstitutionSiteSectionStudents->autoInsertSectionStudent($sectionData);
			}
			$StudentAdmissionTable = TableRegistry::get('Institution.StudentAdmission');
			$EducationGradesTable = TableRegistry::get('Education.EducationGrades');
			$educationSystemId = $EducationGradesTable->getEducationSystemId($entity->education_grade_id);
			$educationGradesToUpdate = $EducationGradesTable->getEducationGradesBySystem($educationSystemId);

			$conditions = [
				'student_id' => $entity->student_id, 
				'academic_period_id' => $entity->academic_period_id, 
				'status' => 0,
				'education_grade_id IN' => $educationGradesToUpdate
			];

			$StudentAdmissionTable->updateAll(
				['status' => 2],
				[$conditions]
			);
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
		$options = [
			'userRole' => 'Student',
			'action' => $this->action,
			'id' => $entity->id,
			'userId' => $entity->student_id
		];

		$tabElements = $this->controller->getUserTabElements($options);

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
			$attr['visible'] = false;
		} else if ($action == 'index') {
			$attr['sort'] = ['field' => 'Users.first_name'];
		}
		return $attr;
	}

	public function onUpdateFieldStudentName(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['type'] = 'autocomplete';
			$attr['target'] = ['key' => 'student_id', 'name' => $this->aliasField('student_id')];
			$attr['noResults'] = $this->getMessage($this->aliasField('noStudents'));
			$attr['attr'] = ['placeholder' => __('OpenEMIS ID or Name')];
			$attr['url'] = ['controller' => 'Institutions', 'action' => 'Students', 'ajaxUserAutocomplete'];

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

	public function onUpdateFieldStudentStatusId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			// 1 - Enrolled, 9 - Pending Admission
			$statusesToShow = ['CURRENT', 'PENDING_ADMISSION'];
			$StudentStatusesTable = $this->StudentStatuses;
			$conditions = [
				$StudentStatusesTable->aliasField('code').' IN' => $statusesToShow
			];
			$options = $StudentStatusesTable
				->find('list')
				->where([$conditions])
				->toArray();
			$attr['options'] = $options;
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
		$this->Session->delete('Institution.Students.new');
	}

	public function addOnNew(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if (isset($data['Students']['academic_period_id'])) {
			// For PHPOE-1916
			$editable = $this->AcademicPeriods->getEditable($data['Students']['academic_period_id']);
			if (! $editable) {
				$this->Alert->error('general.academicPeriod.notEditable');
			} 
			// End PHPOE-1916
			else {
				$this->Session->write('Institution.Students.new', $data[$this->alias()]);
				$event->stopPropagation();
				$action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'StudentUser', 'add'];
				return $this->controller->redirect($action);
			}
		} else {
			$this->Alert->error('Institution.InstitutionSiteStudents.educationProgrammeId');
		}
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
			// only search for students
			$query = $this->Users->find()->where([$this->Users->aliasField('is_student') => 1]);

			$term = trim($term);
			if (!empty($term)) {
				$query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $term]);
			}

			/**
			 * filter out students having 'Enrolled' status
			 */
			$query->where([
				'NOT EXISTS (
					SELECT `id` 
					FROM `institution_students` 
					WHERE `institution_students`.`student_id` = `Users`.`id`
					AND `institution_students`.`student_status_id` = 1
				)'
			]);

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

	public function _getSelectOptions() {
		//Return all required options and their key
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$Grades = TableRegistry::get('Institution.InstitutionSiteGrades');
		$InstitutionSiteSections = TableRegistry::get('Institution.InstitutionSiteSections');
		$institutionId = $this->Session->read('Institution.Institutions.id');

		// Academic Period
		$periodOptions = $AcademicPeriod->getList();
		if (empty($this->request->query['period'])) {
			$this->request->query['period'] = $this->AcademicPeriods->getCurrent();
		}
		$selectedPeriod = $this->queryString('period', $periodOptions);
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
		$sectionOptions = ['0' => __('-- Select Class --')];
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
		if ($action == 'index') { // for promotion button in index page
			if ($this->AccessControl->check(['Institutions', 'Promotion', 'indexEdit'])) {
				$graduateButton = $buttons['index'];
				$graduateButton['url']['action'] = 'Promotion';
				$graduateButton['url'][0] = 'index';
				$graduateButton['url']['mode'] = 'edit';
				$graduateButton['type'] = 'button';
				$graduateButton['label'] = '<i class="fa kd-graduate"></i>';
				$graduateButton['attr'] = $attr;
				$graduateButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
				$graduateButton['attr']['title'] = __('Promotion / Graduation');

				$toolbarButtons['graduate'] = $graduateButton;
				$toolbarButtons['back'] = $buttons['back'];
				$toolbarButtons['back']['type'] = null;
			}

			if ($this->AccessControl->check([$this->controller->name, 'Transfer', 'add'])) {
				$transferButton = $buttons['index'];
				$transferButton['url']['action'] = 'Transfer';
				$transferButton['url'][0] = 'add';
				$transferButton['type'] = 'button';
				$transferButton['label'] = '<i class="fa kd-transfer"></i>';
				$transferButton['attr'] = $attr;
				$transferButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
				$transferButton['attr']['title'] = __('Transfer');

				$toolbarButtons['transfer'] = $transferButton;
				$toolbarButtons['back'] = $buttons['back'];
				$toolbarButtons['back']['type'] = null;
			}
		} else if ($action == 'view') { // for transfer button in view page
			if ($this->AccessControl->check([$this->controller->name, 'TransferRequests', 'add'])) {
				$TransferRequests = TableRegistry::get('Institution.TransferRequests');
				$StudentPromotion = TableRegistry::get('Institution.StudentPromotion');
				$StudentStatuses = TableRegistry::get('Student.StudentStatuses');

				$id = $this->request->params['pass'][1];
				$studentData = $this->get($id);
				$selectedStudent = $studentData->student_id;
				$selectedPeriod = $studentData->academic_period_id;
				$selectedGrade = $studentData->education_grade_id;
				$this->Session->write($TransferRequests->registryAlias().'.id', $id);

				// Show Transfer button only if the Student Status is Current
				$institutionId = $this->Session->read('Institution.Institutions.id');
				
				$student = $StudentPromotion
					->find()
					->where([
						$StudentPromotion->aliasField('institution_id') => $institutionId,
						$StudentPromotion->aliasField('student_id') => $selectedStudent,
						$StudentPromotion->aliasField('academic_period_id') => $selectedPeriod,
						$StudentPromotion->aliasField('education_grade_id') => $selectedGrade
					])
					->first();

				$checkIfCanTransfer = $this->checkIfCanTransfer($student);
				// End

				// Transfer button
				$transferButton = $buttons['back'];
				$transferButton['type'] = 'button';
				$transferButton['label'] = '<i class="fa kd-transfer"></i>';
				$transferButton['attr'] = $attr;
				$transferButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
				$transferButton['attr']['title'] = __('Transfer');
				//End

				$transferRequest = $TransferRequests
						->find()
						->where([
							$TransferRequests->aliasField('previous_institution_id') => $institutionId,
							$TransferRequests->aliasField('student_id') => $selectedStudent,
							$TransferRequests->aliasField('status') => 0
						])
						->first();

				if (!empty($transferRequest)) {
					$transferButton['url'] = [
						'plugin' => $buttons['back']['url']['plugin'],
						'controller' => $buttons['back']['url']['controller'],
						'action' => 'TransferRequests',
						'edit',
						$transferRequest->id
					];
					$toolbarButtons['transfer'] = $transferButton;
				} else if ($checkIfCanTransfer) {
					$transferButton['url'] = [
						'plugin' => $buttons['back']['url']['plugin'],
						'controller' => $buttons['back']['url']['controller'],
						'action' => 'TransferRequests',
						'add'
					];
					$toolbarButtons['transfer'] = $transferButton;
				} 
			}
		}
	}

	private function checkIfCanTransfer($student) {
		$StudentStatuses = TableRegistry::get('Student.StudentStatuses');
		$studentStatusList = array_flip($StudentStatuses->findCodeList());
		
		$checkIfCanTransfer = (in_array($studentStatusList[$student->student_status_id], ['CURRENT', 'PROMOTED', 'GRADUATED']));
		if ($checkIfCanTransfer && $studentStatusList[$student->student_status_id] == 'PROMOTED') {
			//'Promoted' status - this feature will be available if the student is at the last grade that the school offers
			// Education Grades
			$InstitutionEducationGrades = TableRegistry::get('Institution.InstitutionGrades');

			$EducationGrades = TableRegistry::get('Education.EducationGrades');
			$studentEducationGradeOrder = $EducationGrades->find()->where([$EducationGrades->aliasField($EducationGrades->primaryKey()) => $student->education_grade_id])->first();
			if (!empty($studentEducationGradeOrder)) {
				$studentEducationGradeOrder = $studentEducationGradeOrder->order;
			}					

			$advancedGradeOptionsLeft = $InstitutionEducationGrades
				->find('list', [
						'keyField' => 'EducationGrades.order',
						'valueField' => 'EducationGrades.name'
					])
				->select([
						'EducationGrades.id', 'EducationGrades.name', 'EducationGrades.order'
					])
				->contain(['EducationGrades'])
				->where(['EducationGrades.order > ' => $studentEducationGradeOrder])
				->where(['institution_site_id' => $institutionId])
				->group('education_grade_id')
				->count()
				;
				
			if ($advancedGradeOptionsLeft>0) {
				$checkIfCanTransfer = false;
			}
		}
		return $checkIfCanTransfer;
	}

	// Function use by the mini dashboard (For Institution Students)
	public function getNumberOfStudentsByGender($params=[]) {
		$conditions = isset($params['conditions']) ? $params['conditions'] : [];
		$_conditions = [];
		foreach ($conditions as $key => $value) {
			$_conditions[$this->alias().'.'.$key] = $value;
		}
		$studentsConditions = [
			'Users.date_of_death IS NULL',
		];
		$studentsConditions = array_merge($studentsConditions, $_conditions);

		$institutionSiteRecords = $this->find();
		$institutionSiteStudentCount = $institutionSiteRecords
			->contain(['Users', 'Users.Genders'])
			->select([
				'count' => $institutionSiteRecords->func()->count('DISTINCT student_id'),	
				'gender' => 'Genders.name'
			])
			->where($studentsConditions)
			->group('gender');


			
		// Creating the data set		
		$dataSet = [];
		foreach ($institutionSiteStudentCount->toArray() as $value) {
			//Compile the dataset
			$dataSet[] = [__($value['gender']), $value['count']];
		}
		$params['dataSet'] = $dataSet;
		return $params;
	}

	// Function use by the mini dashboard (For Institution Students)
	public function getNumberOfStudentsByAge($params=[]) {
		$conditions = isset($params['conditions']) ? $params['conditions'] : [];
		$_conditions = [];
		foreach ($conditions as $key => $value) {
			$_conditions[$this->alias().'.'.$key] = $value;
		}

		$studentsConditions = [
			'Users.date_of_death IS NULL',
		];

		$studentsConditions = array_merge($studentsConditions, $_conditions);
		$today = Time::today();

		$institutionSiteRecords = $this->find();
		$query = $institutionSiteRecords
			->contain(['Users'])
			->select([
				'age' => $institutionSiteRecords->func()->dateDiff([
					$institutionSiteRecords->func()->now(),
					'Users.date_of_birth' => 'literal'
				]),
				'student' => $this->aliasField('student_id')
			])
			->distinct(['student'])
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
			$dataSet[] = [__('Age').' '.$value['age'], $value['count']];
		}
		$params['dataSet'] = $dataSet;
		return $params;
	}

	// Function use by the mini dashboard (For Institution Students)
	public function getNumberOfStudentsByGradeByInstitution($params=[]) {
		$conditions = isset($params['conditions']) ? $params['conditions'] : [];
		$_conditions = [];
		foreach ($conditions as $key => $value) {
			$_conditions[$this->alias().'.'.$key] = $value;
		}
		$studentsByGradeConditions = [
			$this->aliasField('education_grade_id').' IS NOT NULL',
		];

		$studentsByGradeConditions = array_merge($studentsByGradeConditions, $_conditions);

		$query = $this->find();
		$studentByGrades = $query
			->select([
				'grade' => 'EducationGrades.name',
				'count' => $query->func()->count('DISTINCT '.$this->aliasField('student_id'))
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
			$dataSet[] = [__($value['grade']), $value['count']];
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
					'total' => $query->func()->count('DISTINCT '.$this->aliasField('student_id'))
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
			'OR' => [['student_status_id' => 1], ['student_status_id' => 2]],
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
