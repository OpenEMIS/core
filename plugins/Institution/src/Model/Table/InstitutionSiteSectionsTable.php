<?php
namespace Institution\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteSectionsTable extends AppTable {
	private $_selectedGradeType = 'single';
	private $_selectedAcademicPeriodId = 0;
	private $_selectedEducationGradeId = 0;
	private $_numberOfSections = 1;

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', 		['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Staff', 					['className' => 'User.Users', 						'foreignKey' => 'security_user_id']);
		$this->belongsTo('InstitutionSiteShifts', 	['className' => 'Institution.InstitutionSiteShifts','foreignKey' => 'institution_site_shift_id']);
		$this->belongsTo('Institutions', 			['className' => 'Institution.Institutions', 		'foreignKey' => 'institution_site_id']);

		$this->hasMany('InstitutionSiteSectionGrades', 		['className' => 'Institution.InstitutionSiteSectionGrades']);
		$this->hasMany('InstitutionSiteSectionStudents', 	['className' => 'Institution.InstitutionSiteSectionStudents']);


		$this->InstitutionSiteProgrammes = $this->Institutions->InstitutionSiteProgrammes;
		$this->InstitutionSiteGrades = $this->Institutions->InstitutionSiteGrades;
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('section_number', ['visible' => false]);
		$this->ControllerAction->field('modified_user_id', ['visible' => false]);
		$this->ControllerAction->field('modified', ['visible' => false]);
		$this->ControllerAction->field('created_user_id', ['visible' => false]);
		$this->ControllerAction->field('created', ['visible' => false]);

		$this->ControllerAction->field('academic_period_id', ['visible' => ['view'=>true, 'edit'=>true]]);
		$this->ControllerAction->field('institution_site_shift_id', ['visible' => ['view'=>true, 'edit'=>true]]);

		$this->ControllerAction->field('security_user_id', ['type' => 'string', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);

		$this->ControllerAction->field('male_students', ['type' => 'integer', 'visible' => ['index'=>true]]);
		$this->ControllerAction->field('female_students', ['type' => 'integer', 'visible' => ['index'=>true]]);
		$this->ControllerAction->field('classes', ['type' => 'integer', 'visible' => ['index'=>true]]);

		$categoryOptions = $this->InstitutionSiteSectionStudents->getStudentCategoryList();
		$this->ControllerAction->field('students', [
			'label' => '',
			'type' => 'element',
			'element' => 'Institution.Sections/students',
			'data' => [	
				'students'=>[],
				'studentOptions'=>[],
				'categoryOptions'=>$categoryOptions
			],
			'visible' => ['view'=>true, 'edit'=>true]
		]);
		$this->ControllerAction->field('education_grades', [
			'type' => 'element',
			'element' => 'Institution.Sections/multi_grade',
			'data' => [	
				'grades'=>[]
			],
			'visible' => ['view'=>true]
		]);

		$this->ControllerAction->setFieldOrder([
			'name', 'security_user_id', 
			'male_students', 'female_students', 'classes',
		]);

		if (strtolower($this->action) != 'index') {
			$this->Navigation->addCrumb($this->getHeader($this->action));
		}
	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
    public function indexBeforeAction($event) {
		$query = $this->request->query;
    	if (array_key_exists('grade_type', $query)) {
    		unset($this->ControllerAction->buttons['index']['url']['grade_type']);
			$action = $this->ControllerAction->buttons['index']['url'];
			$this->controller->redirect($action);
    	}

		$Sections = $this;
 		$institutionsId = $this->Session->read('Institutions.id');

		$conditions = array(
			'InstitutionSiteProgrammes.institution_site_id' => $institutionsId
		);
		$academicPeriodOptions = $this->InstitutionSiteProgrammes->getAcademicPeriodOptions($conditions);
		if (empty($academicPeriodOptions)) {
			$this->Alert->warning('Institutions.noProgrammes');
		}
		$this->_selectedAcademicPeriodId = $this->queryString('academic_period_id', $academicPeriodOptions);
		$this->advancedSelectOptions($academicPeriodOptions, $this->_selectedAcademicPeriodId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noSections')),
			'callable' => function($id) use ($Sections, $institutionsId) {
				return $Sections->findByInstitutionSiteIdAndAcademicPeriodId($institutionsId, $id)->count();
			}
		]);

		$gradeOptions = $this->InstitutionSiteGrades->getInstitutionSiteGradeOptions($institutionsId, $this->_selectedAcademicPeriodId);
		$selectedAcademicPeriodId = $this->_selectedAcademicPeriodId;
		if (empty($gradeOptions)) {
			$this->Alert->warning('Institutions.noGrades');
		}
		$this->_selectedEducationGradeId = $this->queryString('education_grade_id', $gradeOptions);
		$this->advancedSelectOptions($gradeOptions, $this->_selectedEducationGradeId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noSections')),
			'callable' => function($id) use ($Sections, $institutionsId, $selectedAcademicPeriodId) {
				$query = $Sections->find()
									->join([
										[
											'table' => 'institution_site_section_grades',
											'alias' => 'InstitutionSiteSectionGrades',
											'conditions' => [
												'InstitutionSiteSectionGrades.institution_site_section_id = InstitutionSiteSections.id',
												'InstitutionSiteSectionGrades.education_grade_id' => $id
											]
										]
									])
									->where([
										$Sections->aliasField('institution_site_id') => $institutionsId,
										$Sections->aliasField('academic_period_id') => $selectedAcademicPeriodId,
									]);
				return $query->count();
			}
		]);


		$toolbarElements = [
            ['name' => 'Institution.Sections/controls', 
             'data' => [
	            	'academicPeriodOptions'=>$academicPeriodOptions, 
	            	'selectedAcademicPeriod'=>$this->_selectedAcademicPeriodId, 
	            	'gradeOptions'=>$gradeOptions, 
	            	'selectedGrade'=>$this->_selectedEducationGradeId, 
	            ],
	         'options' => []
            ]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
    }

	public function indexBeforePaginate($event, $model, $paginateOptions) {
		$paginateOptions['finder'] = ['byGrades' => []];
		$paginateOptions['conditions'][][$this->aliasField('academic_period_id')] = $this->_selectedAcademicPeriodId;
		return $paginateOptions;
	}

    public function findByGrades(Query $query, array $options) {
    	return $query
			->join([
				[
					'table' => 'institution_site_section_grades',
					'alias' => 'InstitutionSiteSectionGrades',
					'conditions' => [
						'InstitutionSiteSectionGrades.institution_site_section_id = InstitutionSiteSections.id',
						'InstitutionSiteSectionGrades.education_grade_id' => $this->_selectedEducationGradeId
					]
				]
			]);
    }




/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
    public function viewBeforeAction($event) {
		$this->ControllerAction->setFieldOrder([
			'academic_period_id', 'name', 'institution_site_shift_id', 'education_grades', 'security_user_id', 'students'
		]);

		$query = $this->request->query;
    	if (array_key_exists('academic_period_id', $query) || array_key_exists('education_grade_id', $query)) {
    		if (array_key_exists('academic_period_id', $query)) {
	    		unset($this->ControllerAction->buttons['view']['url']['academic_period_id']);
    		}
    		if (array_key_exists('education_grade_id', $query)) {
	    		unset($this->ControllerAction->buttons['view']['url']['education_grade_id']);
    		}
    		$action = $this->ControllerAction->buttons['view']['url'];
			$this->controller->redirect($action);
    	}

	}

	public function viewBeforeQuery($event, $query, $contain) {
		$contain = [
			'AcademicPeriods',
			'InstitutionSiteShifts',
			'Staff',
			'InstitutionSiteSectionGrades',
			'InstitutionSiteSectionStudents' => ['EducationGrades', 'Users'=>['Genders']]
		];
		return [$query, $contain];
	}

	public function viewAfterAction($event, $entity) {
		$this->fields['students']['data']['students'] = $entity->institution_site_section_students;
		$this->fields['education_grades']['data']['grades'] = $entity->institution_site_section_grades;

		$academicPeriodOptions = $this->getAcademicPeriodOptions();
	}



/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
    public function addBeforeAction($event) {
		// $this->ControllerAction->setFieldOrder([
		// 	'academic_period_id', 'name', 'institution_site_shift_id', 'education_grades', 'security_user_id', 'students'
		// ]);

		$query = $this->request->query;
    	if (array_key_exists('academic_period_id', $query) || array_key_exists('education_grade_id', $query)) {
    		if (array_key_exists('academic_period_id', $query)) {
	    		unset($this->ControllerAction->buttons['add']['url']['academic_period_id']);
    		}
    		if (array_key_exists('education_grade_id', $query)) {
	    		unset($this->ControllerAction->buttons['add']['url']['education_grade_id']);
    		}
    		$action = $this->ControllerAction->buttons['add']['url'];
			$this->controller->redirect($action);
    	}
    	if (array_key_exists('grade_type', $query)) {
    		$this->_selectedGradeType = $this->ControllerAction->buttons['add']['url']['grade_type'];
    	}
    	if (array_key_exists($this->alias(), $this->request->data)) {
	    	$_data = $this->request->data[$this->alias()];
			$this->_selectedAcademicPeriodId = $_data['academic_period_id'];

			if ($this->_selectedGradeType == 'single') {
				$this->_selectedEducationGradeId = $_data['education_grade'];
				$this->_numberOfSections = $_data['number_of_sections'];
			} elseif ($this->_selectedGradeType == 'multi') {
		    
		    }
	
		}

		$institutionsId = $this->Session->read('Institutions.id');

		/**
		 * academic_period_id field setup
		 */
		$academicPeriodOptions = $this->getAcademicPeriodOptions();
		$this->fields['academic_period_id']['type'] = 'select';
		$this->fields['academic_period_id']['options'] = $academicPeriodOptions;
		$this->fields['academic_period_id']['onChangeReload'] = true;
	
		/**
		 * institution_site_shift_id field setup
		 */
		$this->InstitutionSiteShifts->createInstitutionDefaultShift($institutionsId, $this->_selectedAcademicPeriodId);
		$shiftOptions = $this->InstitutionSiteShifts->getShiftOptions($institutionsId, $this->_selectedAcademicPeriodId);
		$this->fields['institution_site_shift_id']['type'] = 'select';
		$this->fields['institution_site_shift_id']['options'] = $shiftOptions;

		$academicPeriodObj = $this->AcademicPeriods->get($this->_selectedAcademicPeriodId);
		$startDate = $this->AcademicPeriods->getDate($academicPeriodObj->start_date);
        $endDate = $this->AcademicPeriods->getDate($academicPeriodObj->end_date);

		/**
		 * security_user_id field setup
		 */
		$this->fields['security_user_id']['type'] = 'select';
		$staffOptions = $this->getStaffOptions();
		// pr($staffOptions);//die;
		// pr($query->__toString());die;

		/**
		 * add/edit form setup
		 */
		$this->fields['section_number']['visible'] = false;
		if ($this->_selectedGradeType == 'single') {

			/**
			 * education_grade field setup
			 */
			$gradeOptions = $this->Institutions->InstitutionSiteGrades->getInstitutionSiteGradeOptions($institutionsId, $this->_selectedAcademicPeriodId);
			if ($this->_selectedEducationGradeId != 0) {
				if (!array_key_exists($this->_selectedEducationGradeId, $gradeOptions)) {
					$this->_selectedEducationGradeId = key($gradeOptions);
				}
			} else {
				$this->_selectedEducationGradeId = key($gradeOptions);
			}
			$this->ControllerAction->addField('education_grade', [
				'type' => 'select',
				'options' => $gradeOptions,
				'onChangeReload' => true
			]);

			$numberOfSectionsOptions = $this->numberOfSectionsOptions();
			$this->ControllerAction->addField('number_of_sections', [
				'type' => 'select', 
				'order' => 5,
				'options' => $numberOfSectionsOptions,
				'onChangeReload' => true
			]);

			$grade = [];
			if ($this->InstitutionSiteSectionGrades->EducationGrades->exists(['id' => $this->_selectedEducationGradeId])) {
				$grade = $this->InstitutionSiteSectionGrades->EducationGrades->get($this->_selectedEducationGradeId, [
				    'contain' => ['EducationProgrammes']
				])->toArray();
			}
			// $startingSectionNumber = $this->getNewSectionNumber($institutionsId, $this->_selectedEducationGradeId);	
			$startingSectionNumber = $this->getNewSectionNumber($institutionsId);	
			$this->ControllerAction->addField('single_grade_field', [
				'type' => 'element', 
				'order' => 6,
				'element' => 'Institution.Sections/single_grade',
				'data' => [	'numberOfSections'=>$this->_numberOfSections,
				 			'staffOptions'=>$staffOptions,
				 			'startingSectionNumber'=>$startingSectionNumber,
				 			'grade'=>$grade	]
			]);

			$this->fields['name']['visible'] = false;
			$this->fields['security_user_id']['visible'] = false;

			$this->fields['academic_period_id']['order'] = 1;
			$this->fields['education_grade']['order'] = 2;
			$this->fields['institution_site_shift_id']['order'] = 3;
			$this->fields['section_number']['order'] = 4;
    	} else {

			$gradeOptions = $this->Institutions->InstitutionSiteGrades->getInstitutionSiteGradeOptions($institutionsId, $this->_selectedAcademicPeriodId, false);
			$this->ControllerAction->addField('multi_grade_field', [
				'type' => 'element', 
				'order' => 6,
				'element' => 'Institution.Sections/multi_grade',
				'model' => $this->alias(),
				'field' => 'multi_grade_field',
				'data' => $gradeOptions
			]);

			$this->fields['security_user_id']['options'] = $staffOptions;

			$this->fields['academic_period_id']['order'] = 1;
			$this->fields['name']['order'] = 2;
			$this->fields['institution_site_shift_id']['order'] = 3;
			$this->fields['security_user_id']['order'] = 4;

    	}

		$this->Navigation->addCrumb(ucwords(strtolower($this->action)).' '.ucwords(strtolower($this->_selectedGradeType)).' Grade');

		$tabElements = [
			'single' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Sections', $this->action, 'grade_type'=>'single'],
				'text' => __('Single Grade')
			],
			'multi' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Sections', $this->action, 'grade_type'=>'multi'],
				'text' => __('Multi Grade')
			],
		];
        $this->controller->set('tabElements', $tabElements);
	}

	public function addBeforePatch($event, $entity, $data, $options) {
		$commonData = $data['InstitutionSiteSections'];

		if ($this->_selectedGradeType == 'single') {
			foreach($data['MultiSections'] as $key => $row) {
				$data['MultiSections'][$key]['institution_site_shift_id'] = $commonData['institution_site_shift_id'];
				$data['MultiSections'][$key]['institution_site_id'] = $commonData['institution_site_id'];
				$data['MultiSections'][$key]['academic_period_id'] = $commonData['academic_period_id'];
				$data['MultiSections'][$key]['institution_site_section_grades'][0] = [
						'education_grade_id' => $commonData['education_grade'],
						'status' => 1
					];
			}
			$data['InstitutionSiteSections'] = $data['MultiSections'];
			unset($data['MultiSections']);
			
			$sections = $this->newEntities($data['InstitutionSiteSections']);
			$error = false;
			foreach ($sections as $section) {
			    if ($section->errors()) {
			    	$error = $section->errors();
			    }
			}
			if (!$error) {
				foreach ($sections as $section) {
			    	$this->save($section);
				}
				$this->Alert->success('general.add.success');
				$action = $this->ControllerAction->buttons['index']['url'];
				$this->controller->redirect($action);
			} else {
				// pr($error);
				$this->log($error, 'debug');
				$this->Alert->error('general.add.failed');
			}
			return false;
		} else {
			foreach($data['InstitutionSiteSections']['institution_site_section_grades'] as $key => $row) {
				$data['InstitutionSiteSections']['institution_site_section_grades'][$key]['status'] = 1;
			}
			return compact('entity', 'data', 'options');
		}
	}

	public function addAfterAction($event, $entity) {
        $this->controller->set('selectedAction', $this->_selectedGradeType);

		if ($entity->academic_period_id == '') {
			$this->fields['academic_period_id']['attr']['value'] = $this->_selectedAcademicPeriodId;
		}
		if (isset($entity->education_grade) && $entity->education_grade  == '') {
			$this->fields['education_grade']['attr']['value'] = $this->_selectedEducationGradeId;
		}
		if (isset($entity->number_of_sections)) { 
			if($entity->number_of_sections  == '') {
				$this->fields['number_of_sections']['attr']['value'] = $this->_numberOfSections;
				$this->fields['single_grade_field']['data']['numberOfSections'] = $this->_numberOfSections;
			} else {
				$this->fields['single_grade_field']['data']['numberOfSections'] = $entity->number_of_sections;
			}
		}
	}



/******************************************************************************************************************
**
** edit action methods
**
******************************************************************************************************************/
	public function editBeforeAction($event) {

		$this->ControllerAction->setFieldOrder([
			'academic_period_id', 'name', 
			'institution_site_shift_id', 'security_user_id', 'students',
		]);

	}

	public function editBeforeQuery($event, $query, $contain) {
		$contain = [
			'AcademicPeriods',
			'InstitutionSiteShifts',
			'Staff',
			'InstitutionSiteSectionGrades',
			'InstitutionSiteSectionStudents' => ['EducationGrades', 'Users'=>['Genders']]
		];
		return [$query, $contain];
	}

	public function editBeforePatch($event, $entity, $data, $options) {
		/**
		 * Unable to utilise updateAll for this scenario.
		 * Only new student records will be saved as status=1 at the later part of this scope.
		 * Existitng records which is not removed from the UI list, will remain as status=0 instead of 1.
		 */
		// $this->InstitutionSiteSectionStudents->updateAll(['status'=>0], ['institution_site_section_id' => $entity->id]);


		/**
		 * In students.ctp, we set the security_user_id as the array keys for easy search and compare.
		 * Assign back original record's id to the new list so as to preserve id numbers.
		 */
		foreach($entity->institution_site_section_students as $key => $record) {
			$k = $record->security_user_id;
			if (!array_key_exists($k, $data[$this->alias()]['institution_site_section_students'])) {			
				$data[$this->alias()]['institution_site_section_students'][$k] = [
					'id' => $k,
					'status' => 0 
				];
			} else {
				$data[$this->alias()]['institution_site_section_students'][$k]['id'] = $record->id;
			}
		}
		return compact('entity', 'data', 'options');
	}

	public function editAfterAction($event, $entity) {
		$students = $entity->institution_site_section_students;
		$studentOptions = $this->getStudentsOptions($entity);
		/**
		 * Check if the request is a page reload
		 */
		if (count($this->request->data)>0 && $this->request->data['submit']=='add') {
			// clear institution_site_section_students list grab from db
			$existingStudents = $students;
			$students = [];
			/**
			 * Populate records in the UI table & unset the record from studentOptions
			 */
			if (array_key_exists('institution_site_section_students', $this->request->data[$this->alias()])) {
				foreach ($this->request->data[$this->alias()]['institution_site_section_students'] as $row) {
					if ($row['status'] == 1 && array_key_exists($row['security_user_id'], $studentOptions)) {
						$id = $row['security_user_id'];
						$students[] = $this->createVirtualStudentEntity($id, $entity);
						unset($studentOptions[$id]);
					}
				}
			}
			/**
			 * Insert the newly record into the UI table & unset the record from studentOptions
			 */
			if (array_key_exists('student_id', $this->request->data)) {
				$id = $this->request->data['student_id'];
				$students[] = $this->createVirtualStudentEntity($id, $entity);
				unset($studentOptions[$id]);
			}
		} else {
			/**
			 * Just unset the record from studentOptions on first page load
			 */
			foreach ($entity->institution_site_section_students as $row) {
				if ($row->status == 1 && array_key_exists($row->security_user_id, $studentOptions)) {
					unset($studentOptions[$row->security_user_id]);
				}
			}
		}
		$this->fields['students']['data']['students'] = $students;
		$this->fields['students']['data']['studentOptions'] = $studentOptions;

		$gradeOptions = $this->getSectionGradeOptions($entity);
		$this->fields['students']['data']['gradeOptions'] = $gradeOptions;

	}



/******************************************************************************************************************
**
** field specific methods
**
******************************************************************************************************************/

	/**
	 * academic_period_id field setup
	 */
	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request) {
		$academicPeriodOptions = $this->getAcademicPeriodOptions();
		if ($action == 'edit') {

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $academicPeriodOptions[$this->_selectedAcademicPeriodId];

		} elseif ($action == 'add') {

			$attr['options'] = $academicPeriodOptions;

		}

		return $attr;
	}

	/**
	 * institution_site_shift_id field setup
	 */
	public function onUpdateFieldInstitutionSiteShiftId(Event $event, array $attr, $action, $request) {
		$institutionsId = $this->Session->read('Institutions.id');
		if ($action == 'edit') {

			$this->InstitutionSiteShifts->createInstitutionDefaultShift($institutionsId, $this->_selectedAcademicPeriodId);
			$shiftOptions = $this->InstitutionSiteShifts->getShiftOptions($institutionsId, $this->_selectedAcademicPeriodId);
			$attr['type'] = 'select';
			$attr['options'] = $shiftOptions;

		} elseif ($action == 'add') {

			// $attr['options'] = $academicPeriodOptions;

		}

		return $attr;
	}

	/**
	 * security_user_id field setup
	 */
	public function onUpdateFieldSecurityUserId(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {

			$attr['type'] = 'select';
			$attr['options'] = $this->getStaffOptions();

		} elseif ($action == 'add') {

			// $attr['options'] = $academicPeriodOptions;

		}

		return $attr;
	}




/******************************************************************************************************************
**
** essential functions
**
******************************************************************************************************************/

	protected function getSectionGradeOptions($entity) {
		$Grade = $this->InstitutionSiteSectionGrades;
		$gradeOptions = $Grade->find()
							->contain('EducationGrades')
							->where([
								$Grade->aliasField('institution_site_section_id') => $entity->id,
								$Grade->aliasField('status') => 1
							])
							->toArray();
		$options = [];
		foreach ($gradeOptions as $key => $value) {
			$options[$value->education_grade->id] = $value->education_grade->name;
		}
		return $options;
	}

	protected function getStudentsOptions($sectionEntity) {
		$institutionsId = $this->Session->read('Institutions.id');
		$academicPeriodObj = $this->AcademicPeriods->get($this->_selectedAcademicPeriodId);
		$startDate = $this->AcademicPeriods->getDate($academicPeriodObj->start_date);
        $endDate = $this->AcademicPeriods->getDate($academicPeriodObj->end_date);

		$sectionGradeObjects = $sectionEntity->institution_site_section_grades;
		$sectionGrades = [];
		foreach ($sectionGradeObjects as $key=>$value) {
			$sectionGrades[] = $value->education_grade_id;
		}

		$students = $this->Institutions->InstitutionSiteStudents;
		$query = $students->find();
		$query = $query->contain(['Users', 'EducationProgrammes'=>['EducationGrades']]);
		$query = $query->where([
				$students->aliasField('institution_site_id') => $institutionsId,
				'OR' => array(
					'OR' => array(
						array(
							$students->aliasField('end_date').' IS NOT NULL',
							$students->aliasField('start_date').' <= "' . $startDate . '"',
							$students->aliasField('end_date').' >= "' . $startDate . '"'
						),
						array(
							$students->aliasField('end_date').' IS NOT NULL',
							$students->aliasField('start_date').' <= "' . $endDate . '"',
							$students->aliasField('end_date').' >= "' . $endDate . '"'
						),
						array(
							$students->aliasField('end_date').' IS NOT NULL',
							$students->aliasField('start_date').' >= "' . $startDate . '"',
							$students->aliasField('end_date').' <= "' . $endDate . '"'
						)
					),
					array(
						$students->aliasField('end_date').' IS NULL',
						$students->aliasField('start_date').' <= "' . $endDate . '"'
					)
				)
			]);

		$list = $query->toArray();
		$studentOptions = [__('Select Student')];
		foreach ($list as $skey => $obj) {
			$studentGradeEligible = $obj->education_programme->education_grades;
			$studentGradeKeys = array();
			foreach ($studentGradeEligible as $key => $value) {
				$studentGradeKeys[] = $value->id;
			}

			$studentProgramEligible = false;
			foreach ($studentGradeKeys as $key => $value) {
				if (in_array($value, $sectionGrades)) {
					$studentProgramEligible = true;
				}
			}
			if ($studentProgramEligible) {
				if (isset($obj->user)) {
					$studentOptions[$obj->user->id] = $obj->user->name_with_id;
				} else {
					$this->log('Data corrupted with no security user for student id: '. $obj->id, 'debug');
				}
			}
		}

		$studentOptions = $this->attachSectionInfo($sectionEntity->id, $studentOptions, $institutionsId, $this->_selectedAcademicPeriodId);
		return $studentOptions;
	}

	public function attachSectionInfo($id, $studentOptions, $institutionsId, $periodId) {
		$query = $this->InstitutionSiteSectionStudents->find()
					->contain(['InstitutionSiteSections'])
					->where([
						$this->aliasField('institution_site_id') => $institutionsId,
						$this->aliasField('academic_period_id') => $periodId,
					])
					->where([
							$this->InstitutionSiteSectionStudents->aliasField('security_user_id').' IN' => array_keys($studentOptions),
							$this->InstitutionSiteSectionStudents->aliasField('status') => 1
						]);
		$sectionsWithStudents = $query->toArray();

		foreach($sectionsWithStudents as $student) {
			if($student->institution_site_section_id != $id) {
				if (!isset($studentOptions[$student->institution_site_section->name])) {
					$studentOptions[$student->institution_site_section->name] = ['text' => 'Section '.$student->institution_site_section->name, 'options' => [], 'disabled' => true];
				}
				$studentOptions[$student->institution_site_section->name]['options'][] = ['value' => $student->security_user_id, 'text' => $studentOptions[$student->security_user_id]];
				unset($studentOptions[$student->security_user_id]);
			}
		}
		return $studentOptions;
	}

	protected function getStaffOptions() {
		$institutionsId = $this->Session->read('Institutions.id');
		$academicPeriodObj = $this->AcademicPeriods->get($this->_selectedAcademicPeriodId);
		$startDate = $this->AcademicPeriods->getDate($academicPeriodObj->start_date);
        $endDate = $this->AcademicPeriods->getDate($academicPeriodObj->end_date);

        // TODO-Hanafi: add date conditions as commented below
        // pr($startDate);
        // pr($endDate);
        $Staff = $this->Institutions->InstitutionSiteStaff;
		$query = $Staff->find('all')
						->find('withBelongsTo')
						->find('byInstitution', ['Institutions.id'=>$institutionsId])
						->where([
							$Staff->aliasField('end_date') . ' IS NULL',
							$Staff->aliasField('start_date') . ' >= ' . $endDate
						])
						// ->where(['OR' => [
						// 			[
						// 				$Staff->aliasField('end_date') . ' IS NOT NULL',
						// 				$Staff->aliasField('start_date') . ' <= ' . $startDate,
						// 				$Staff->aliasField('end_date') . ' >= ' . $startDate
						// 			],
						// 			[
						// 				$Staff->aliasField('end_date') . ' IS NOT NULL',
						// 				$Staff->aliasField('start_date') . ' <= ' . $endDate,
						// 				$Staff->aliasField('end_date') . ' >= ' . $endDate
						// 			],
						// 			[
						// 				$Staff->aliasField('end_date') . ' IS NOT NULL',
						// 				$Staff->aliasField('start_date') . ' >= ' . $startDate,
						// 				$Staff->aliasField('end_date') . ' <= ' . $endDate
						// 			]
						// 		]
						// ])
							;
		$options = [];
		foreach ($query->toArray() as $key => $value) {
			$options[$value->user->id] = $value->user->name;
		}
		return $options;
	}

	public function numberOfSectionsOptions() {
		$total = 10;
		$options = array();
		for($i=1; $i<=$total; $i++){
			$options[$i] = $i;
		}
		
		return $options;
	}
	
	// public function getNewSectionNumber($institutionSiteId, $gradeId) {
	public function getNewSectionNumber($institutionsId) {
		$data = $this->find()
					->where([
						'institution_site_id' => $institutionsId,
						// 'education_grade_id' => $gradeId
					])
					->order(['section_number DESC'])
					->first()
					;
		
		$number = 1;
		if(!empty($data)){
			$number = $data->section_number + 1;
		}
		
		return $number;
	}
	
	protected function createVirtualStudentEntity($id, $entity) {
		$userData = $this->Institutions->InstitutionSiteStudents->find()->contain(['Users'=>['Genders']])->where(['security_user_id'=>$id])->first();
		$data = [
			'id'=>$this->getExistingRecordId($id, $entity),
			'security_user_id'=>$id,
			'institution_site_section_id'=>$entity->id,
			'education_grade_id'=>0,
			'student_category_id'=>0,
			'status'=>1,
			'user'=>[]
		];
		$student = $this->InstitutionSiteSectionStudents->newEntity();
		$student = $this->InstitutionSiteSectionStudents->patchEntity($student, $data);
		$student->user = $userData->user;
		return $student;
	}

	protected function getExistingRecordId($securityId, $entity) {
		$id = '';
		foreach ($entity->institution_site_section_students as $student) {
			if ($student->security_user_id == $securityId) {
				$id = $student->id;
			}
		}
		return $id;
	}

	private function getAcademicPeriodOptions() {
		$institutionsId = $this->Session->read('Institutions.id');
		$conditions = array(
			'InstitutionSiteProgrammes.institution_site_id' => $institutionsId
		);
		$list = $this->InstitutionSiteProgrammes->getAcademicPeriodOptions($conditions);
		if (empty($list)) {
			$this->Alert->warning('Institutions.noProgramme');
			return $this->redirect($this->ControllerAction->buttons['index']['url']);
		} else {
			if ($this->_selectedAcademicPeriodId != 0) {
				if (!array_key_exists($this->_selectedAcademicPeriodId, $list)) {
					$this->_selectedAcademicPeriodId = key($list);
				}
			} else {
				$this->_selectedAcademicPeriodId = key($list);
			}
		}
		return $list;

	}
	
	public function getSectionOptions($academicPeriodId, $institutionsId, $gradeId=false) {
		$singleGradeOptions = array(
			'fields' => array('InstitutionSiteSections.id', 'InstitutionSiteSections.name'),
			'conditions' => array(
				'InstitutionSiteSections.academic_period_id' => $academicPeriodId,
				'InstitutionSiteSections.institution_site_id' => $institutionsId
			),
			'order' => array('InstitutionSiteSections.name')
		);

		$multiGradeOptions = array(
			'fields' => array('InstitutionSiteSections.id', 'InstitutionSiteSections.name'),
			'conditions' => array(
				'InstitutionSiteSections.academic_period_id' => $academicPeriodId,
				'InstitutionSiteSections.institution_site_id' => $institutionsId
			),
			'order' => array('InstitutionSiteSections.name')
		);

		if($gradeId!==false) {
			$singleGradeOptions['conditions']['InstitutionSiteSections.education_grade_id'] = $gradeId;

			$multiGradeOptions['joins'] = array(
				array(
					'table' => 'institution_site_section_grades',
					'alias' => 'InstitutionSiteSectionGrade',
					'conditions' => array(
						'InstitutionSiteSectionGrades.institution_site_section_id = InstitutionSiteSections.id',
						'InstitutionSiteSectionGrades.education_grade_id = ' . $gradeId,
						'InstitutionSiteSectionGrades.status = 1'
					)
				)
			);
			$multiGradeOptions['group'] = array('InstitutionSiteSections.id');
		}

		if($gradeId!==false && is_null($gradeId)) {
			$singleGradeData = [];
			$multiGradeData = [];
		} else {
			$singleGradeData = $this->find('list', $singleGradeOptions);
			$multiGradeData = $this->find('list', $multiGradeOptions);
		}

		$data = array_replace($singleGradeData, $multiGradeData);
		return $data;
	}
}
