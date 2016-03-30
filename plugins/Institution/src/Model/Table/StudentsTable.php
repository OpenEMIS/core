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
use Cake\ORM\ResultSet;

class StudentsTable extends AppTable {
	const PENDING_TRANSFER = -2;
	const PENDING_ADMISSION = -3;
	const PENDING_DROPOUT = -4;

	private $dashboardQuery = null;
	
	public function initialize(array $config) {
		$this->table('institution_students');
		parent::initialize($config);

		$this->belongsTo('Users',			['className' => 'Security.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('StudentStatuses',	['className' => 'Student.StudentStatuses']);
		$this->belongsTo('EducationGrades',	['className' => 'Education.EducationGrades']);
		$this->belongsTo('Institutions',	['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('AcademicPeriods',	['className' => 'AcademicPeriod.AcademicPeriods']);

		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
		$this->addBehavior('AcademicPeriod.Period');
		// to handle field type (autocomplete)
		$this->addBehavior('OpenEmis.Autocomplete');
		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');
		$this->addBehavior('Institution.StudentCascadeDelete'); // for cascade delete on student related tables from an institution
		$this->addBehavior('AcademicPeriod.AcademicPeriod');

		$this->addBehavior('Excel', [
			'excludes' => ['start_year', 'end_year'], 
			'pages' => ['index']
		]);

		$this->addBehavior('HighChart', [
			'number_of_students_by_year' => [
				'_function' => 'getNumberOfStudentsByYear',
				'chart' => ['type' => 'column', 'borderWidth' => 1],
				'xAxis' => ['title' => ['text' => __('Years')]],
				'yAxis' => ['title' => ['text' => __('Total')]]
			],
			'number_of_students_by_grade' => [
				'_function' => 'getNumberOfStudentsByGrade',
				'chart' => ['type' => 'column', 'borderWidth' => 1],
				'xAxis' => ['title' => ['text' => __('Education')]],
				'yAxis' => ['title' => ['text' => __('Total')]]
			],
			'institution_student_gender' => [
				'_function' => 'getNumberOfStudentsByGender'
			],
			'institution_student_age' => [
				'_function' => 'getNumberOfStudentsByAge'
			],
			'institution_section_student_grade' => [
				'_function' => 'getNumberOfStudentsByGradeByInstitution'
			]
		]);
        $this->addBehavior('Import.ImportLink');
        $this->addBehavior('Institution.UpdateStudentStatus');

		/**
		 * Advance Search Types.
		 * AdvanceSearchBehavior must be included first before adding other types of advance search.
		 * If no "belongsTo" relation from the main model is needed, include its foreign key name in AdvanceSearch->exclude options.
		 */
		$this->addBehavior('AdvanceSearch', [
			'exclude' => [
				'student_id',
				'institution_id',
				'education_grade_id',
				'academic_period_id',
				'student_status_id',
			]
		]);
		$this->addBehavior('User.AdvancedIdentitySearch', [
			'associatedKey' => $this->aliasField('student_id')
		]);
		$this->addBehavior('User.AdvancedContactNumberSearch', [
			'associatedKey' => $this->aliasField('student_id')
		]);
		$this->addBehavior('User.AdvancedSpecificNameTypeSearch', [
			'modelToSearch' => $this->Users
		]);
		/**
		 * End Advance Search Types
		 */
		
	}

	public function validationDefault(Validator $validator) {
		$validator
			->add('start_date', 'ruleCompareDate', [
				'rule' => ['compareDate', 'end_date', false]
			])
			->add('end_date', [
			])
			->add('student_status_id', [
			])
			->add('academic_period_id', [
			])
			->add('student_name', 'ruleInstitutionStudentId', [
				'rule' => ['institutionStudentId'],
				'on' => 'create',
				'last' => true
			])
			->add('student_name', 'ruleCheckAdmissionAgeWithEducationCycleGrade', [
				'rule' => ['checkAdmissionAgeWithEducationCycleGrade'],
				'on' => 'create'
			])
			->add('student_name', 'ruleStudentEnrolledInOthers', [
				'rule' => ['checkEnrolledInOtherInstitution'],
				'on' => 'create'
			])
			->add('class', 'ruleClassMaxLimit', [
				'rule' => ['checkInstitutionClassMaxLimit'],
				'on' => 'create'
			])
			;
		return $validator;
	}

	// no longer needed. student_name will be filled if it is not there to trigger hte validation and avoid the required validation fail
	// public function validationAllowEmptyName(Validator $validator) {
	// 	$validator = $this->validationDefault($validator);
	// 	// PHPOE-1919
	// 	// hanafi and malcolm made changes on this branch. 
	// 	// $validator->remove('student_name'); is the old code
	// 	// this is latest
	// 	$validator->allowEmpty('student_name');
	// 	// die;

	// 	return $validator;
	// }

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$query->where([$this->aliasField('institution_id') => $institutionId]);
		$query->contain(['Users.Nationalities.Countries', 'Users.Identities.IdentityTypes', 'Users.Genders']);
		$query->select(['openemis_no' => 'Users.openemis_no', 'gender_id' => 'Genders.name', 'date_of_birth' => 'Users.date_of_birth', 'code' => 'Institutions.code']);
		$periodId = $this->request->query['academic_period_id'];
		if ($periodId > 0) {
			$query->where([$this->aliasField('academic_period_id') => $periodId]);
		}
		$query->leftJoin(['SectionStudents' => 'institution_section_students'], [
				'SectionStudents.student_id = '.$this->aliasField('student_id'), 
				'SectionStudents.education_grade_id = '.$this->aliasField('education_grade_id'),
				'SectionStudents.student_status_id = '.$this->aliasField('student_status_id')
			])->leftJoin(['Sections' => 'institution_sections'], [
				'Sections.id = SectionStudents.institution_section_id', 
				'Sections.institution_id = '.$this->aliasField('institution_id'),
				'Sections.academic_period_id = '.$this->aliasField('academic_period_id')
			])->select(['institution_section_name' => 'Sections.name']);
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
		$fieldCopy = $fields->getArrayCopy();
		$newFields = [];

		foreach ($fieldCopy as $key => $field) {
			if ($field['field'] != 'institution_id') {
			$newFields[] = $field;
				if ($field['field'] == 'education_grade_id') {
					$newFields[] = [
						'key' => 'StudentClasses.institution_section_id',
						'field' => 'institution_section_name',
						'type' => 'string',
						'label' => ''
					];
				}
			}
		}
		
		$extraField[] = [
			'key' => 'Institutions.code',
			'field' => 'code',
			'type' => 'string',
			'label' => '',
		];

		$extraField[] = [
			'key' => 'Students.institution_id',
			'field' => 'institution_id',
			'type' => 'integer',
			'label' => '',
		];

		$extraField[] = [
			'key' => 'Users.openemis_no',
			'field' => 'openemis_no',
			'type' => 'string',
			'label' => ''
		];

		$extraField[] = [
			'key' => 'Users.gender_id',
			'field' => 'gender_id',
			'type' => 'string',
			'label' => ''
		];

		$extraField[] = [
			'key' => 'Users.date_of_birth',
			'field' => 'date_of_birth',
			'type' => 'date',
			'label' => ''
		];

		$extraField[] = [
			'key' => 'Identities.number',
			'field' => 'number',
			'type' => 'identities',
			'label' => __('Identities')
		];

		$extraField[] = [
			'key' => 'Nationalities.country_id',
			'field' => 'country_id',
			'type' => 'nationalities',
			'label' => __('Nationalities')
		];

		$newFields = array_merge($extraField, $newFields);
		$fields->exchangeArray($newFields);
	}

	public function onExcelRenderIdentities(Event $event, Entity $entity, array $attr) {
		$str = '';
		if(!empty($entity['user']['identities'])) {
			$identities = $entity['user']['identities'];
			foreach ($identities as $identity) {
				$number = $identity['number'];
				$identityType = $identity['identity_type']['name'];
				$str .= '('.$identityType.') '.$number.', ';
			}
		}
		if (!empty($str)) {
			$str = substr($str, 0, -2);
		}
		return $str;
	}

	public function onExcelRenderNationalities(Event $event, Entity $entity, array $attr) {
		$str = '';
		if(!empty($entity['user']['nationalities'])) {
			$nationalities = $entity['user']['nationalities'];
			foreach ($nationalities as $nationality) {
				if (isset($nationality['country']['name'])) {
					$str .= $nationality['country']['name'].', ';
				}			
			}
		}
		if (!empty($str)) {
			$str = substr($str, 0, -2);
		}
		return $str;
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

	// Start PHPOE-2123
	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		// Another check to check before deletion. In case of concurrency issue.
		$status = $this->get($id)->student_status_id;
		$studentStatuses = $this->StudentStatuses->findCodeList();
		if ($status != $studentStatuses['CURRENT']) {
			$process = function() use ($id, $options) {
				$this->Alert->error('Institution.InstitutionStudents.deleteNotEnrolled');
			};
			return $process;
		}
	}
	// End PHPOE-2123

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->ControllerAction->field('academic_period_id', ['visible' => false]);
		$this->ControllerAction->field('class', ['order' => 90]);
		$this->ControllerAction->field('student_status_id', ['order' => 100]);
		$this->fields['start_date']['visible'] = false;
		$this->fields['end_date']['visible'] = false;
		$this->fields['class']['sort'] = ['field' => 'InstitutionSections.name'];
		
		$StudentStatusesTable = $this->StudentStatuses;
		$status = $StudentStatusesTable->findCodeList();
		$selectedStatus = $this->request->query('status_id');
		switch ($selectedStatus) {
			case self::PENDING_ADMISSION:
				$event->stopPropagation();
				return $this->controller->redirect(['plugin'=>'Institution', 'controller' => 'Institutions', 'action' => 'StudentAdmission']);
				break;
			case self::PENDING_TRANSFER:
				$event->stopPropagation();
				return $this->controller->redirect(['plugin'=>'Institution', 'controller' => 'Institutions', 'action' => 'TransferRequests']);
				break;
			case self::PENDING_DROPOUT:
				$event->stopPropagation();
				return $this->controller->redirect(['plugin'=>'Institution', 'controller' => 'Institutions', 'action' => 'StudentDropout']);
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
		$conditions = [
			$this->aliasField('student_id') => $studentId,
			$this->aliasField('academic_period_id') => $academicPeriodId,
			$this->aliasField('student_status_id') => $this->StudentStatuses->getIdByCode('CURRENT')
		];

		if (is_null($academicPeriodId)) {
			$conditions = [
				$this->aliasField('student_id') => $studentId,
				$this->aliasField('student_status_id') => $this->StudentStatuses->getIdByCode('CURRENT')
			];
		}

		$gradeIds = $this->find('list', [
					'keyField' => 'education_grade_id',
					'valueField' => 'education_grade_id'
				])
				->where([$conditions])
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

		$pendingStatus = [
			self::PENDING_TRANSFER => __('Pending Transfer'),
			self::PENDING_ADMISSION => __('Pending Admission'),
			self::PENDING_DROPOUT => __('Pending Dropout'),
		];

		$statusOptions = $statusOptions + $pendingStatus;

		// Academic Periods
		$academicPeriodOptions = $this->AcademicPeriods->getList();

		// Education Grades
		$InstitutionEducationGrades = TableRegistry::get('Institution.InstitutionGrades');
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
			->where(['institution_id' => $institutionId])
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

		$request->query['academic_period_id'] = $selectedAcademicPeriod;

		$this->advancedSelectOptions($educationGradesOptions, $selectedEducationGrades);


		if ($selectedEducationGrades != -1) {
			$query->where([$this->aliasField('education_grade_id') => $selectedEducationGrades]);
		}

		if ($selectedStatus != -1) {
			$query->where([$this->aliasField('student_status_id') => $selectedStatus]);
		}

		$query->where([$this->aliasField('academic_period_id') => $selectedAcademicPeriod]);

		// Start: sort by class column
		$session = $request->session();
		$institutionId = $session->read('Institution.Institutions.id');
		$query->find('withClass', ['institution_id' => $institutionId, 'period_id' => $selectedAcademicPeriod]);

		$sortList = ['openemis_no', 'first_name', 'InstitutionSections.name'];
		if (array_key_exists('sortWhitelist', $options)) {
			$sortList = array_merge($options['sortWhitelist'], $sortList);
		}
		$options['sortWhitelist'] = $sortList;
		// End

		$search = $this->ControllerAction->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
		}

		$this->controller->set(compact('statusOptions', 'academicPeriodOptions', 'educationGradesOptions'));
	}

	public function findWithClass(Query $query, array $options) {
		$institutionId = $options['institution_id'];
		$periodId = $options['period_id'];

		$ClassStudents = TableRegistry::get('Institution.InstitutionSectionStudents');
		$Classes = TableRegistry::get('Institution.InstitutionSections');

		return $query
			->select([$Classes->aliasField('name')])
			->leftJoin(
				[$ClassStudents->alias() => $ClassStudents->table()],
				[
					$ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
					$ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
					$ClassStudents->aliasField('student_status_id = ') . $this->aliasField('student_status_id')
				]
			)
			->leftJoin(
				[$Classes->alias() => $Classes->table()],
				[
					$Classes->aliasField('id = ') . $ClassStudents->aliasField('institution_section_id'),
					$Classes->aliasField('academic_period_id') => $periodId,
					$Classes->aliasField('institution_id') => $institutionId
				]
			)
			->autoFields(true);
	}

	public function indexAfterPaginate(Event $event, ResultSet $resultSet) {
		$query = $resultSet->__debugInfo()['query'];
		$this->dashboardQuery = clone $query;
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

		$ClassStudents = TableRegistry::get('Institution.InstitutionSectionStudents');
		$class = $ClassStudents->find()
		->select(['class.name'])
		->innerJoin(
			['class' => 'institution_sections'],
			[
				'class.id = ' . $ClassStudents->aliasField('institution_section_id'),
				'class.academic_period_id' => $academicPeriodId,
				'class.institution_id' => $institutionId
			]
		)
		->where([
			$ClassStudents->aliasField('student_id') => $studentId,
			$ClassStudents->aliasField('education_grade_id') => $educationGradeId
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
			$InstitutionArray = [];
			$institutionStudentQuery = clone $this->dashboardQuery;
			$studentCount = $institutionStudentQuery->group([$this->aliasField('student_id')])->count();
			unset($institutionStudentQuery);

			//Get Gender
			$InstitutionArray[__('Gender')] = $this->getDonutChart('institution_student_gender', 
				['query' => $this->dashboardQuery, 'key' => __('Gender')]);
			
			// Get Age
			$InstitutionArray[__('Age')] = $this->getDonutChart('institution_student_age', 
				['query' => $this->dashboardQuery, 'key' => __('Age')]);

			// Get Grades
			$InstitutionArray[__('Grade')] = $this->getDonutChart('institution_section_student_grade', 
				['query' => $this->dashboardQuery, 'key' => __('Grade')]);

			$indexDashboard = 'dashboard';
			$indexElements = $this->controller->viewVars['indexElements'];
			
			$indexElements[] = ['name' => 'Institution.Students/controls', 'data' => [], 'options' => [], 'order' => 0];
			
			$indexElements[] = [
				'name' => $indexDashboard,
				'data' => [
					'model' => 'students',
					'modelCount' => $studentCount,
					'modelArray' => $InstitutionArray,
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
				}
			}
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

	public function viewAfterAction(Event $event, Entity $entity) {
		$studentStatusId = $entity->student_status_id;
		$statuses = $this->StudentStatuses->findCodeList();
		$code = array_search($studentStatusId, $statuses);

		if ($code == 'DROPOUT' || $code == 'TRANSFERRED') {
			$this->ControllerAction->field('reason', ['type' => 'custom_status_reason']);
			$this->ControllerAction->field('comment');
			$this->ControllerAction->setFieldOrder([
				'photo_content', 'openemis_no', 'student_id', 'student_status_id', 'reason', 'comment'
			]);
		}
		$this->Session->write('Student.Students.id', $entity->student_id);
		$this->Session->write('Student.Students.name', $entity->user->name);
		$this->setupTabElements($entity);
	}

	private function setupTabElements($entity) {
		$options['type'] = 'student';
		$tabElements = TableRegistry::get('Student.Students')->getAcademicTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Programmes');
	}

	public function onGetCustomStatusReasonElement(Event $event, $action, $entity, $attr, $options=[]) {
		if ($this->action == 'view') {
			$studentStatusId = $entity->student_status_id;
			$statuses = $this->StudentStatuses->findCodeList();
			$code = array_search($studentStatusId, $statuses);
			$institutionId = $entity->institution_id;
			$educationGradeId = $entity->education_grade_id;
			$studentId = $entity->student_id;
			$academicPeriodId = $entity->academic_period_id;

			switch ($code) {
				case 'TRANSFERRED':
					$TransferApprovalsTable = TableRegistry::get('Institution.TransferApprovals');
					$transferReason = $TransferApprovalsTable->find()
						->matching('StudentTransferReasons')
						->where([
							// Type = 2 is transfer type
							$TransferApprovalsTable->aliasField('type') => 2,
							$TransferApprovalsTable->aliasField('academic_period_id') => $academicPeriodId,
							$TransferApprovalsTable->aliasField('previous_institution_id') => $institutionId,
							$TransferApprovalsTable->aliasField('education_grade_id') => $educationGradeId,
							$TransferApprovalsTable->aliasField('academic_period_id') => $academicPeriodId
						])
						->first();

					$entity->comment = $transferReason->comment;

					return $transferReason->_matchingData['StudentTransferReasons']->name;
					break;

				case 'DROPOUT':
					$DropoutRequestsTable = TableRegistry::get('Institution.DropoutRequests');

					$dropoutReason = $DropoutRequestsTable->find()
						->matching('StudentDropoutReasons')
						->where([
							$DropoutRequestsTable->aliasField('academic_period_id') => $academicPeriodId,
							$DropoutRequestsTable->aliasField('institution_id') => $institutionId,
							$DropoutRequestsTable->aliasField('education_grade_id') => $educationGradeId,
							$DropoutRequestsTable->aliasField('academic_period_id') => $academicPeriodId
						])
						->first();

					$entity->comment = $dropoutReason->comment;

					return $dropoutReason->_matchingData['StudentDropoutReasons']->name;
					break;
			}
		}
	}

	public function onGetComment(Event $event, Entity $entity) {
		if ($this->action == 'view') {
			return nl2br($entity->comment);
		}
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		if ($this->action == 'add') {
			$buttons[0]['name'] = '<i class="fa kd-add"></i> ' . __('Create New');
			$buttons[0]['attr']['value'] = 'new';
		}
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

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		if ($entity->isNew()) {
			$StudentStatuses = TableRegistry::get('Student.StudentStatuses');
			if ($StudentStatuses->get($entity->student_status_id)->code == 'CURRENT') {
				// to automatically add the student into a specific class when the student is successfully added to a school
				if ($entity->has('class') && $entity->class > 0) {
					$sectionData = [];
					$sectionData['student_id'] = $entity->student_id;
					$sectionData['education_grade_id'] = $entity->education_grade_id;
					$sectionData['institution_section_id'] = $entity->class;
					$sectionData['student_status_id'] = $entity->student_status_id;
					$InstitutionSectionStudents = TableRegistry::get('Institution.InstitutionSectionStudents');
					$InstitutionSectionStudents->autoInsertSectionStudent($sectionData);
				}

				// the logic below is to set all pending admission applications to rejected status once the student is successfully enrolled in a school
				$StudentAdmissionTable = TableRegistry::get('Institution.StudentAdmission');
				$EducationGradesTable = TableRegistry::get('Education.EducationGrades');
				$educationSystemId = $EducationGradesTable->getEducationSystemId($entity->education_grade_id);
				$educationGradesToUpdate = $EducationGradesTable->getEducationGradesBySystem($educationSystemId);

				$conditions = [
					'student_id' => $entity->student_id, 
					'status' => 0,
					'education_grade_id IN' => $educationGradesToUpdate
				];

				$StudentAdmissionTable->updateAll(
					['status' => 2],
					[$conditions]
				);
			}
		}
	}

	public function viewBeforeAction(Event $event) {
		$this->ControllerAction->field('photo_content', ['type' => 'image', 'order' => 0]);
		$this->ControllerAction->field('openemis_no', ['type' => 'readonly', 'order' => 1]);
		$this->fields['student_id']['order'] = 10;
	}

	public function editBeforeQuery(Event $event, Query $query) {
		$query->contain(['Users', 'EducationGrades', 'AcademicPeriods', 'StudentStatuses']);
	}

	public function editAfterAction(Event $event, Entity $entity) {

		// Start PHPOE-1897
		$statuses = $this->StudentStatuses->findCodeList();
		if ($entity->student_status_id != $statuses['CURRENT']) {
			$event->stopPropagation();
			$urlParams = $this->ControllerAction->url('view');
			return $this->controller->redirect($urlParams);
		}
		// End PHPOE-1897

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

		$this->Session->write('Student.Students.id', $entity->student_id);
		$this->Session->write('Student.Students.name', $entity->user->name);
		$this->setupTabElements($entity);
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
			foreach ($options as $key => $value) {
				$options[$key] = __($value);
			}
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
			// pr('onAddNew');pr($data['Students']);pr($data[$this->alias()]);die;
				$this->Session->write('Institution.Students.new', $data[$this->alias()]);
				$event->stopPropagation();
				$action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'StudentUser', 'add'];
				return $this->controller->redirect($action);
			}
		} else {
			$this->Alert->error('Institution.InstitutionStudents.educationProgrammeId');
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
		$Grades = TableRegistry::get('Institution.InstitutionGrades');
		$InstitutionSections = TableRegistry::get('Institution.InstitutionSections');
		$institutionId = $this->Session->read('Institution.Institutions.id');

		// Academic Period
		$periodOptions = $AcademicPeriod->getList(['isEditable'=>true]);
		if (empty($this->request->query['period'])) {
			$this->request->query['period'] = $this->AcademicPeriods->getCurrent();
		}
		$selectedPeriod = $this->queryString('period', $periodOptions);
		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
			'callable' => function($id) use ($Grades, $institutionId) {
				return $Grades
					->find()
					->where([$Grades->aliasField('institution_id') => $institutionId])
					->find('academicPeriod', ['academic_period_id' => $id])
					->count();
			}
		]);
		// End

		// Grade
		$gradeOptions = [];
		$sectionOptions = [];
		$selectedSection = 0;

		if ($selectedPeriod != 0) {
			$data = $Grades->find()
			->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
			->contain('EducationGrades.EducationProgrammes')
			->where([$Grades->aliasField('institution_id') => $institutionId])
			->all();

			foreach ($data as $entity) {
				$gradeOptions[$entity->education_grade->id] = $entity->education_grade->programme_grade_name;
			}

			$selectedGrade = !is_null($this->request->query('grade')) ? $this->request->query('grade') : key($gradeOptions);
			$this->advancedSelectOptions($gradeOptions, $selectedGrade, []);
			// End
			
			// section
			$sectionOptions = $sectionOptions + $InstitutionSections->getSectionOptions($selectedPeriod, $institutionId, $selectedGrade);
			// End
		}
		
		return compact('periodOptions', 'selectedPeriod', 'gradeOptions', 'selectedGrade', 'sectionOptions', 'selectedSection');
	}
	
	// Start PHPOE-1897
	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		$studentId = $this->get($entity->id)->student_id;
		$institutionId = $entity->institution_id;
		if (isset($buttons['view'])) {
			$url = $this->ControllerAction->url('view');
			$url['action'] = 'StudentUser';
			$url[1] = $entity['_matchingData']['Users']['id'];
			$url['id'] = $entity->id;
			$buttons['view']['url'] = $url;
		}

		if (isset($buttons['edit'])) {
			$url = $this->ControllerAction->url('edit');
			$url['action'] = 'StudentUser';
			$url[1] = $entity['_matchingData']['Users']['id'];
			$url['id'] = $entity->id;
			$buttons['edit']['url'] = $url;
		}

		if (! $this->checkEnrolledInInstitution($studentId, $institutionId)) {
			if (isset($buttons['edit'])) {
				unset($buttons['edit']);
			}
			if (isset($buttons['remove'])) {
				unset($buttons['remove']);
			}
		}

		$status = $entity->student_status_id;
		$studentStatuses = $this->StudentStatuses->findCodeList();
		if ($status != $studentStatuses['CURRENT']) {
			if (isset($buttons['remove'])) {
				unset($buttons['remove']);
			}
		}
		return $buttons;
	}
	// End PHPOE-1897

	public function checkEnrolledInInstitution($studentId, $institutionId) {
		$statuses = TableRegistry::get('Student.StudentStatuses')->findCodeList();
		$status = $this
			->find()
			->where([$this->aliasField('student_id') => $studentId, 
				$this->aliasField('institution_id') => $institutionId,
				$this->aliasField('student_status_id') => $statuses['CURRENT']
			])
			->count();
		return $status > 0;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'index') { // for promotion button in index page
			if ($this->AccessControl->check(['Institutions', 'Promotion', 'add'])) {
				$graduateButton = $buttons['index'];
				$graduateButton['url']['action'] = 'Promotion';
				$graduateButton['url'][0] = 'add';
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

			if ($this->AccessControl->check(['Institutions', 'Undo', 'add'])) {
				$undoButton = $buttons['index'];
				$undoButton['url']['action'] = 'Undo';
				$undoButton['url'][0] = 'add';
				$undoButton['type'] = 'button';
				$undoButton['label'] = '<i class="fa fa-undo"></i>';
				$undoButton['attr'] = $attr;
				$undoButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
				$undoButton['attr']['title'] = __('Undo');

				$toolbarButtons['undo'] = $undoButton;
				$toolbarButtons['back'] = $buttons['back'];
				$toolbarButtons['back']['type'] = null;
			}
		} else if ($action == 'view') { // for transfer button in view page
			$statuses = $this->StudentStatuses->findCodeList();
			$id = $this->request->params['pass'][1];
			$studentStatusId = $this->get($id)->student_status_id;			
			// Start PHPOE-1897
			if ($studentStatusId != $statuses['CURRENT']) {
				if (isset($toolbarButtons['edit'])) {
					unset($toolbarButtons['edit']);
				}
			}
			// End PHPOE-1897

			if (isset($toolbarButtons['back'])) {
				$refererUrl = $this->request->referer();
				$toolbarButtons['back']['url'] = $refererUrl;
			}
		}
	}

	public function checkIfCanTransfer($student, $institutionId) {
		$StudentStatuses = TableRegistry::get('Student.StudentStatuses');
		$studentStatusList = array_flip($StudentStatuses->findCodeList());
		
		$checkIfCanTransfer = (in_array($studentStatusList[$student->student_status_id], ['CURRENT', 'PROMOTED', 'GRADUATED']));
		if ($checkIfCanTransfer && $studentStatusList[$student->student_status_id] == 'PROMOTED') {
			//'Promoted' status - this feature will be available if the student is at the last grade that the school offers
			// Education Grades
			$InstitutionEducationGrades = TableRegistry::get('Institution.InstitutionGrades');

			$EducationGrades = TableRegistry::get('Education.EducationGrades');

			$systemId = $EducationGrades->getEducationSystemId($student->education_grade_id);
	    	// Check all academic period for enrol
	    	$academicPeriodId = null;
	    	$studentId = $student->student_id;
	    	if ($this->checkIfEnrolledInAllInstitution($studentId, $academicPeriodId, $systemId)) {
	    		return false;
	    	}

			$studentEducationGrade = $EducationGrades
				->find()
				->where([$EducationGrades->aliasField($EducationGrades->primaryKey()) => $student->education_grade_id])
				->first();

			$currentProgrammeGrades = $EducationGrades
				->find('list', [
					'keyField' => 'id',
					'valueField' => 'programme_grade_name'
				])
				->find('visible')
				->where([
					$this->EducationGrades->aliasField('order').' > ' => $studentEducationGrade->order,
					$this->EducationGrades->aliasField('education_programme_id') => $studentEducationGrade->education_programme_id
				])
				->toArray();

			$EducationProgrammesNextProgrammesTable = TableRegistry::get('Education.EducationProgrammesNextProgrammes');
			$educationProgrammeId = $studentEducationGrade->education_programme_id;
			$nextEducationGradeList = $EducationProgrammesNextProgrammesTable->getNextGradeList($educationProgrammeId);
			$moreAdvancedEducationGrades = $currentProgrammeGrades + $nextEducationGradeList;

			$studentEducationGradeOrder = [];
			if (!empty($studentEducationGrade)) {
				$studentEducationGradeOrder = $studentEducationGrade->order;
			}
	
			$advancedGradeOptionsLeft = $InstitutionEducationGrades
				->find('list', [
						'keyField' => 'EducationGrades.id',
						'valueField' => 'EducationGrades.name'
					])
				->select([
						'EducationGrades.id', 'EducationGrades.name', 'EducationGrades.order'
					])
				->contain(['EducationGrades'])
				->where(['EducationGrades.order > ' => $studentEducationGradeOrder])
				->where(['institution_id' => $institutionId])
				->group('education_grade_id')
				->toArray()
				;
				
			if (count(array_intersect_key($moreAdvancedEducationGrades, $advancedGradeOptionsLeft))>0) {
				$checkIfCanTransfer = false;
			} else {
				$checkIfCanTransfer = true;
			}
		}
		return $checkIfCanTransfer;
	}

	// Function use by the mini dashboard (For Institution Students)
	public function getNumberOfStudentsByGender($params=[]) {
		$query = $params['query'];
		$InstitutionRecords = clone $query;
		$InstitutionStudentCount = $InstitutionRecords
			->matching('Users.Genders')
			->select([
				'count' => $InstitutionRecords->func()->count('DISTINCT ' . $this->aliasField('student_id')),
				'gender' => 'Genders.name'
			])
			->group(['gender']);
			
		// Creating the data set		
		$dataSet = [];
		foreach ($InstitutionStudentCount->toArray() as $value) {
			//Compile the dataset
			$dataSet[] = [__($value['gender']), $value['count']];
		}
		$params['dataSet'] = $dataSet;
		unset($InstitutionRecords);
		return $params;
	}

	// Function use by the mini dashboard (For Institution Students)
	public function getNumberOfStudentsByAge($params=[]) {
		$query = $params['query'];
		$InstitutionRecords = clone $query;
		$ageQuery = $InstitutionRecords
			->select([
				'age' => $InstitutionRecords->func()->dateDiff([
					$InstitutionRecords->func()->now(),
					'Users.date_of_birth' => 'literal'
				]),
				'student' => $this->aliasField('student_id')
			])
			->distinct(['student'])
			->order('age');

		$InstitutionStudentCount = $ageQuery->toArray();

		$convertAge = [];
		
		// (Logic to be reviewed)
		// Calculate the age taking account to the average of leap years 
		foreach($InstitutionStudentCount as $val){
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
		unset($InstitutionRecords);
		return $params;
	}

	// Function use by the mini dashboard (For Institution Students)
	public function getNumberOfStudentsByGradeByInstitution($params=[]) {
		$query = $params['query'];
		$InstitutionRecords = clone $query;
		$studentByGrades = $InstitutionRecords
			->select([
				'grade' => 'EducationGrades.name',
				'count' => $query->func()->count('DISTINCT '.$this->aliasField('student_id'))
			])
			->contain([
				'EducationGrades'
			])
			->group([
				$this->aliasField('education_grade_id'),
			])
			->toArray();

		$dataSet = [];
		foreach($studentByGrades as $value){
			$dataSet[] = [__($value['grade']), $value['count']];
		}
		$params['dataSet'] = $dataSet;
		unset($InstitutionRecords);
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

		// $params['options']['subtitle'] = array('text' => 'For Year '. $currentYear);
		$params['options']['subtitle'] = array('text' => sprintf(__('For Year %s'), $currentYear));
		$params['options']['xAxis']['categories'] = array_values($grades);
		$params['dataSet'] = $dataSet;

		return $params;
	}
}
