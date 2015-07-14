<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;

class InstitutionSiteSectionsTable extends AppTable {
	public $institutionId = 0;
	private $_numberOfSections = 1;
	private $_selectedGradeType = 'single';
	private $_selectedAcademicPeriodId = -1;
	private $_selectedEducationGradeId = 0;

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', 		['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Staff', 					['className' => 'User.Users', 						'foreignKey' => 'security_user_id']);
		$this->belongsTo('InstitutionSiteShifts', 	['className' => 'Institution.InstitutionSiteShifts','foreignKey' => 'institution_site_shift_id']);
		$this->belongsTo('Institutions', 			['className' => 'Institution.Institutions', 		'foreignKey' => 'institution_site_id']);

		$this->hasMany('InstitutionSiteSectionGrades', 		['className' => 'Institution.InstitutionSiteSectionGrades', 'dependent' => true]);
		$this->hasMany('InstitutionSiteSectionStudents', 	['className' => 'Institution.InstitutionSiteSectionStudents', 'dependent' => true]);

		$this->belongsToMany('InstitutionSiteClasses', [
			'className' => 'Institution.InstitutionSiteClasses',
			'joinTable' => 'institution_site_section_classes',
			'foreignKey' => 'institution_site_section_id',
			'targetForeignKey' => 'institution_site_class_id'
		]);

		$this->InstitutionSiteProgrammes = $this->Institutions->InstitutionSiteProgrammes;
		$this->InstitutionSiteGrades = $this->Institutions->InstitutionSiteGrades;
	}

	public function validationDefault(Validator $validator) {
		$validator->requirePresence('name');
		return $validator;
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('section_number', ['visible' => false]);
		$this->ControllerAction->field('modified_user_id', ['visible' => false]);
		$this->ControllerAction->field('modified', ['visible' => false]);
		$this->ControllerAction->field('created_user_id', ['visible' => false]);
		$this->ControllerAction->field('created', ['visible' => false]);

		$this->ControllerAction->field('academic_period_id', ['type' => 'select', 'visible' => ['view'=>true, 'edit'=>true]]);
		$this->ControllerAction->field('institution_site_shift_id', ['type' => 'select', 'visible' => ['view'=>true, 'edit'=>true]]);

		$this->ControllerAction->field('security_user_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);

		$this->ControllerAction->field('male_students', ['type' => 'integer', 'visible' => ['index'=>true]]);
		$this->ControllerAction->field('female_students', ['type' => 'integer', 'visible' => ['index'=>true]]);
		$this->ControllerAction->field('classes', ['type' => 'integer', 'visible' => ['index'=>true]]);

		$categoryOptions = $this->InstitutionSiteSectionStudents->getStudentCategoryList();
		$this->ControllerAction->field('students', [
			'label' => '',
			'override' => true,
			'type' => 'element',
			'element' => 'Institution.Sections/students',
			'data' => [	
				'students'=>[],
				'studentOptions'=>[],
				'categoryOptions'=>$categoryOptions
			],
			'visible' => ['view'=>true, 'edit'=>true]
			// 'visible' => false
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
	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
    public function indexBeforeAction(Event $event) {
		$query = $this->request->query;
    	if (array_key_exists('grade_type', $query)) {
    		unset($this->ControllerAction->buttons['index']['url']['grade_type']);
			$action = $this->ControllerAction->buttons['index']['url'];
			$this->controller->redirect($action);
    	}

		$Sections = $this;

		$conditions = array(
			'InstitutionSiteProgrammes.institution_site_id' => $this->institutionId
		);
		$academicPeriodOptions = $this->InstitutionSiteProgrammes->getAcademicPeriodOptions($conditions);
		if (empty($academicPeriodOptions)) {
			$this->Alert->warning('Institutions.noProgrammes');
		}
		$institutionId = $this->institutionId;
		$this->_selectedAcademicPeriodId = $this->queryString('academic_period_id', $academicPeriodOptions);
		$this->advancedSelectOptions($academicPeriodOptions, $this->_selectedAcademicPeriodId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noSections')),
			'callable' => function($id) use ($Sections, $institutionId) {
				return $Sections->findByInstitutionSiteIdAndAcademicPeriodId($institutionId, $id)->count();
			}
		]);

		$gradeOptions = $this->InstitutionSiteGrades->getInstitutionSiteGradeOptions($this->institutionId, $this->_selectedAcademicPeriodId);
		$selectedAcademicPeriodId = $this->_selectedAcademicPeriodId;
		if (empty($gradeOptions)) {
			$this->Alert->warning('Institutions.noGrades');
		}
		$this->_selectedEducationGradeId = $this->queryString('education_grade_id', $gradeOptions);
		$this->advancedSelectOptions($gradeOptions, $this->_selectedEducationGradeId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noSections')),
			'callable' => function($id) use ($Sections, $institutionId, $selectedAcademicPeriodId) {
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
										$Sections->aliasField('institution_site_id') => $institutionId,
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

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $paginateOptions) {
		$paginateOptions['finder'] = ['byGrades' => []];
		$paginateOptions['conditions'][][$this->aliasField('academic_period_id')] = $this->_selectedAcademicPeriodId;
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
    public function viewBeforeAction(Event $event) {
		if ($this->_selectedAcademicPeriodId == -1) {
			return $this->controller->redirect([
				'plugin' => $this->controller->plugin, 
				'controller' => $this->controller->name, 
				'action' => 'Sections'
			]);
		}

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

		$this->ControllerAction->setFieldOrder([
			'academic_period_id', 'name', 'institution_site_shift_id', 'education_grades', 'security_user_id', 'students'
		]);

	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain([
			'AcademicPeriods',
			'InstitutionSiteShifts',
			'Staff',
			'InstitutionSiteSectionGrades.EducationGrades',
			'InstitutionSiteSectionStudents.Users.Genders'
		]);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->fields['students']['data']['students'] = $entity->institution_site_section_students;
		$this->fields['education_grades']['data']['grades'] = $entity->institution_site_section_grades;

		$academicPeriodOptions = $this->getAcademicPeriodOptions();
	}


/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
    public function addBeforeAction(Event $event) {
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

		if ($this->_selectedAcademicPeriodId == -1) {
			return $this->controller->redirect([
				'plugin' => $this->controller->plugin, 
				'controller' => $this->controller->name, 
				'action' => 'Sections'
			]);
		}

		/**
		 * add/edit form setup
		 */
		$staffOptions = $this->getStaffOptions();
		if ($this->_selectedGradeType == 'single') {
	    	if (array_key_exists($this->alias(), $this->request->data)) {
		    	$_data = $this->request->data[$this->alias()];
				$this->_selectedEducationGradeId = $_data['education_grade'];
				$this->_numberOfSections = $_data['number_of_sections'];
			}

			/**
			 * education_grade field setup
			 */
			$gradeOptions = $this->Institutions->InstitutionSiteGrades->getInstitutionSiteGradeOptions($this->institutionId, $this->_selectedAcademicPeriodId);
			if ($this->_selectedEducationGradeId != 0) {
				if (!array_key_exists($this->_selectedEducationGradeId, $gradeOptions)) {
					$this->_selectedEducationGradeId = key($gradeOptions);
				}
			} else {
				$this->_selectedEducationGradeId = key($gradeOptions);
			}
			$this->ControllerAction->field('education_grade', [
				'type' => 'select',
				'options' => $gradeOptions,
				'onChangeReload' => true
			]);

			$numberOfSectionsOptions = $this->numberOfSectionsOptions();
			$this->ControllerAction->field('number_of_sections', [
				'type' => 'select', 
				'options' => $numberOfSectionsOptions,
				'onChangeReload' => true
			]);

			$grade = [];
			if ($this->InstitutionSiteSectionGrades->EducationGrades->exists(['id' => $this->_selectedEducationGradeId])) {
				$grade = $this->InstitutionSiteSectionGrades->EducationGrades->get($this->_selectedEducationGradeId, [
				    'contain' => ['EducationProgrammes']
				])->toArray();
			}
			$startingSectionNumber = $this->getNewSectionNumber();	
			$this->ControllerAction->field('single_grade_field', [
				'type' => 'element', 
				'element' => 'Institution.Sections/single_grade',
				'data' => [	'numberOfSections'=>$this->_numberOfSections,
				 			'staffOptions'=>$staffOptions,
				 			'startingSectionNumber'=>$startingSectionNumber,
				 			'grade'=>$grade	]
			]);


			$this->fields['name']['visible'] = false;
			$this->fields['students']['visible'] = false;
			$this->fields['security_user_id']['visible'] = false;
			$this->ControllerAction->setFieldOrder([
				'academic_period_id', 'education_grade', 'institution_site_shift_id', 'section_number', 'number_of_sections', 'single_grade_field'
			]);

    	} else {

			$gradeOptions = $this->Institutions->InstitutionSiteGrades->getInstitutionSiteGradeOptions($this->institutionId, $this->_selectedAcademicPeriodId, false);
			$this->ControllerAction->field('multi_grade_field', [
				'type' => 'element', 
				'element' => 'Institution.Sections/multi_grade',
				'model' => $this->alias(),
				'field' => 'multi_grade_field',
				'data' => $gradeOptions
			]);
			$this->fields['security_user_id']['options'] = $staffOptions;
			$this->fields['students']['visible'] = false;
			$this->ControllerAction->setFieldOrder([
				'academic_period_id', 'name', 'institution_site_shift_id', 'security_user_id', 'multi_grade_field'
			]);

    	}

		$this->Navigation->substituteCrumb(ucwords(strtolower($this->action)), ucwords(strtolower($this->action)).' '.ucwords(strtolower($this->_selectedGradeType)).' Grade');

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

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
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
				return $this->controller->redirect($action);
			} else {
				$errorMessage='';
				foreach ($error as $key=>$value) {
					$errorMessage .= Inflector::classify($key);
				}
				$this->log($error, 'debug');
				/**
				 * unset all field validation except for "name" to trigger validation error in ControllerActionComponent
				 */
				foreach ($this->fields as $value) {
					if ($value['field'] != 'name') {
						$this->validator()->remove($value['field']);
					}
				}
				$this->Alert->error('Institution.'.$this->alias().'.empty'.$errorMessage);
			}
		} else {
			if (isset($data['InstitutionSiteSections']['institution_site_section_grades']) && count($data['InstitutionSiteSections']['institution_site_section_grades'])>0) {
				foreach($data['InstitutionSiteSections']['institution_site_section_grades'] as $key => $row) {
					$data['InstitutionSiteSections']['institution_site_section_grades'][$key]['status'] = 1;
				}
			} else {
				/**
				 * set institution_site_id to empty to trigger validation error in ControllerActionComponent
				 */
				$data['InstitutionSiteSections']['institution_site_id'] = '';
				$this->Alert->error('Institution.'.$this->alias().'.noGrade');
			}
		}
	}

	public function addAfterAction(Event $event, Entity $entity) {
        $this->controller->set('selectedAction', $this->_selectedGradeType);

		// if ($entity->academic_period_id == '') {
		// 	$this->fields['academic_period_id']['attr']['value'] = $this->_selectedAcademicPeriodId;
		// }
		// if (isset($entity->education_grade) && $entity->education_grade  == '') {
		// 	$this->fields['education_grade']['attr']['value'] = $this->_selectedEducationGradeId;
		// }
		// if (isset($entity->number_of_sections)) { 
		// 	if($entity->number_of_sections  == '') {
		// 		$this->fields['number_of_sections']['attr']['value'] = $this->_numberOfSections;
		// 		$this->fields['single_grade_field']['data']['numberOfSections'] = $this->_numberOfSections;
		// 	} else {
		// 		$this->fields['single_grade_field']['data']['numberOfSections'] = $entity->number_of_sections;
		// 	}
		// }
	}


/******************************************************************************************************************
**
** edit action methods
**
******************************************************************************************************************/
	public function editBeforeAction(Event $event) {
		if ($this->_selectedAcademicPeriodId == -1) {
			return $this->controller->redirect([
				'plugin' => $this->controller->plugin, 
				'controller' => $this->controller->name, 
				'action' => 'Sections'
			]);
		}

		$this->ControllerAction->setFieldOrder([
			'academic_period_id', 'name', 'institution_site_shift_id', 'security_user_id', 'students',
		]);
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		/**
		 * Unable to utilise updateAll for this scenario.
		 * Only new student records will be saved as status=1 at the later part of this scope.
		 * Existitng records which is not removed from the UI list, will remain as status=0 instead of 1.
		 */
		// $this->InstitutionSiteSectionStudents->updateAll(['status'=>0], ['institution_site_section_id' => $entity->id]);

		// pr($data);
		/**
		 * In students.ctp, we set the security_user_id as the array keys for easy search and compare.
		 * Assign back original record's id to the new list so as to preserve id numbers.
		 */
		foreach($entity->institution_site_section_students as $key => $record) {
			$k = $record->security_user_id;
			if (array_key_exists('institution_site_section_students', $data[$this->alias()])) {
				if (!array_key_exists($k, $data[$this->alias()]['institution_site_section_students'])) {			
					$data[$this->alias()]['institution_site_section_students'][$k] = [
						'id' => $record->id,
						'status' => 0 
					];
				} else {
					$data[$this->alias()]['institution_site_section_students'][$k]['id'] = $record->id;
				}
			} else {
				$data[$this->alias()]['institution_site_section_students'][$k] = [
					'id' => $record->id,
					'status' => 0 
				];
			}
		}
	}

	public function editAfterAction(Event $event, Entity $entity) {
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
		if (array_key_exists($this->alias(), $this->request->data)) {
			$this->_selectedAcademicPeriodId = $this->postString('academic_period_id', $academicPeriodOptions);
		}
		if ($action == 'edit') {
		
			$attr['type'] = 'readonly';
			if ($this->_selectedAcademicPeriodId > -1) {
				$attr['attr']['value'] = $academicPeriodOptions[$this->_selectedAcademicPeriodId];
			}

		} elseif ($action == 'add') {

			$attr['options'] = $academicPeriodOptions;
			$attr['onChangeReload'] = true;
		
		}

		return $attr;
	}

	/**
	 * institution_site_shift_id field setup
	 */
	public function onUpdateFieldInstitutionSiteShiftId(Event $event, array $attr, $action, $request) {
		
		if ($action == 'edit' || $action == 'add') {

			if ($this->_selectedAcademicPeriodId > -1) {
				$this->InstitutionSiteShifts->createInstitutionDefaultShift($this->institutionId, $this->_selectedAcademicPeriodId);
				$shiftOptions = $this->InstitutionSiteShifts->getShiftOptions($this->institutionId, $this->_selectedAcademicPeriodId);
			} else {
				$shiftOptions = [];
			}
			$attr['options'] = $shiftOptions;

		}

		return $attr;
	}

	/**
	 * security_user_id field setup
	 */
	public function onUpdateFieldSecurityUserId(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {

			if ($this->_selectedAcademicPeriodId > -1) {
				$attr['options'] = $this->getStaffOptions();
			}

		} elseif ($action == 'add') {

			// $attr['type'] = 'select';

		} elseif ($action == 'view') {

			// $attr['type'] = 'select';
			if ($this->_selectedAcademicPeriodId > -1) {
				$attr['options'] = $this->getStaffOptions();
			}
			
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
				$students->aliasField('institution_site_id') => $this->institutionId,
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
		$studentOptions = [$this->getMessage('Users.select_student')];
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
					$this->log('Data corrupted with no security user for student: '. $obj->id, 'debug');
				}
			}
		}

		$studentOptions = $this->attachSectionInfo($sectionEntity->id, $studentOptions, $this->institutionId, $this->_selectedAcademicPeriodId);
		return $studentOptions;
	}

	public function attachSectionInfo($id, $studentOptions, $institutionId, $periodId) {
		$query = $this->InstitutionSiteSectionStudents->find()
					->contain(['InstitutionSiteSections'])
					->where([
						$this->aliasField('institution_site_id') => $this->institutionId,
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
		
		$academicPeriodObj = $this->AcademicPeriods->get($this->_selectedAcademicPeriodId);
		$startDate = $this->AcademicPeriods->getDate($academicPeriodObj->start_date);
        $endDate = $this->AcademicPeriods->getDate($academicPeriodObj->end_date);

        // TODO-Hanafi: add date conditions as commented below
        // pr($startDate);
        // pr($endDate);
        $Staff = $this->Institutions->InstitutionSiteStaff;
		$query = $Staff->find('all')
						->find('withBelongsTo')
						->find('byInstitution', ['Institutions.id'=>$this->institutionId])
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
			if ($value->has('user')) {
				$options[$value->user->id] = $value->user->name;
			}
		}
		return $options;
	}

	private function numberOfSectionsOptions() {
		$total = 10;
		$options = array();
		for($i=1; $i<=$total; $i++){
			$options[$i] = $i;
		}
		
		return $options;
	}
	
	private function getNewSectionNumber() {
		$data = $this->find()
					->where([
						'institution_site_id' => $this->institutionId,
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
		
		$conditions = array(
			'InstitutionSiteProgrammes.institution_site_id' => $this->institutionId
		);
		$list = $this->InstitutionSiteProgrammes->getAcademicPeriodOptions($this->Alert, $conditions);
		if (!empty($list)) {
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
	
	/**
	 * Used by Institution/UserBehavior && Institution/InstitutionSiteStudentsTable
	 * @param  [integer]  $academicPeriodId [description]
	 * @param  [integer]  $institutionId    [description]
	 * @param  boolean $gradeId          [description]
	 * @return [type]                    [description]
	 */
	public function getSectionOptions($academicPeriodId, $institutionId, $gradeId=false) {
	// 	$singleGradeOptions = array(
	// 		'fields' => array('InstitutionSiteSections.id', 'InstitutionSiteSections.name'),
	// 		'conditions' => array(
	// 			'InstitutionSiteSections.academic_period_id' => $academicPeriodId,
	// 			'InstitutionSiteSections.institution_site_id' => $institutionId
	// 		),
	// 		'order' => array('InstitutionSiteSections.name')
	// 	);

		$multiGradeOptions = array(
			'fields' => array('InstitutionSiteSections.id', 'InstitutionSiteSections.name'),
			'conditions' => array(
				'InstitutionSiteSections.academic_period_id' => $academicPeriodId,
				'InstitutionSiteSections.institution_site_id' => $institutionId
			),
			'order' => array('InstitutionSiteSections.name')
		);

		if($gradeId!==false) {
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

		$multiGradeData = $this->find('list', $multiGradeOptions);
		return $multiGradeData->toArray();
	}
}
	