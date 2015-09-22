<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Collection\Collection;

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

		/**
		 * Shortcuts
		 */
		$this->InstitutionSiteProgrammes = $this->Institutions->InstitutionSiteProgrammes;
		$this->InstitutionSiteGrades = $this->Institutions->InstitutionSiteGrades;
		// $this->InstitutionSiteGrades = $this->Institutions->InstitutionSiteGrades;

		// this behavior restricts current user to see All Classes or My Classes
		$this->addBehavior('Security.InstitutionClass');
		$this->addBehavior('AcademicPeriod.AcademicPeriod');
	}

	public function validationDefault(Validator $validator) {
		$validator
			->requirePresence('name')
			->add('name', 'ruleUnique', [
	        		'rule' => 'uniqueNamePerAcademicPeriod',
	        		'provider' => 'table',
	        		'message' => 'Section name has to be unique'
			    ])
			;
		return $validator;
	}

	public static function uniqueNamePerAcademicPeriod($field, array $globalData) {
		$data = $globalData['data'];
		$model = $globalData['providers']['table'];
		$exists = $model->find('all')
			->select(['id'])
			->where([
				$model->aliasField('academic_period_id') => $globalData['data']['academic_period_id'],
				$model->aliasField('institution_site_id') => $globalData['data']['institution_site_id'],
				$model->aliasField('name') => $field,
			])
			->toArray();
		if (!empty($exists)) {
			foreach ($exists as $key => $value) {
				if ($value->id == $data['id']) {
					return true;
					break;
				}
			}
			return false;
		} else {
			return true;
		}
	}

	public function beforeAction(Event $event) {
		$academicPeriodOptions = $this->getAcademicPeriodOptions();
		if ($this->action == 'index') {
			if (empty($this->request->query['academic_period_id'])) {
				$this->request->query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
			}
		}
		if (array_key_exists($this->alias(), $this->request->data)) {
			$this->_selectedAcademicPeriodId = $this->postString('academic_period_id', $academicPeriodOptions);
		} else if ($this->action == 'edit' && isset($this->request->pass[1])) {
			$id = $this->request->pass[1];
			if ($this->exists($id)) {
				$this->_selectedAcademicPeriodId = $this->get($id)->academic_period_id;
			}
		}

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
		$this->ControllerAction->field('classes', ['label' => 'Subjects', 'override' => true, 'type' => 'integer', 'visible' => ['index'=>true]]);

		$this->ControllerAction->field('students', [
			'label' => '',
			'override' => true,
			'type' => 'element',
			'element' => 'Institution.Sections/students',
			'data' => [	
				'students'=>[],
				'studentOptions'=>[]
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
			'name', 'security_user_id', 'male_students', 'female_students', 'classes',
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
			$action = $this->ControllerAction->url('index');//$this->ControllerAction->buttons['index']['url'];
			unset($action['grade_type']);
			$this->controller->redirect($action);
    	}

		$Sections = $this;

		// $conditions = array(
		// 	'InstitutionSiteProgrammes.institution_site_id' => $this->institutionId
		// );
		//$academicPeriodOptions = $this->InstitutionSiteProgrammes->getAcademicPeriodOptions($conditions);
		$academicPeriodOptions = $this->AcademicPeriods->getList();
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

		$gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptions($this->institutionId, $this->_selectedAcademicPeriodId);
		$selectedAcademicPeriodId = $this->_selectedAcademicPeriodId;
		if (empty($gradeOptions)) {
			$this->Alert->warning('Institutions.noGrades');
		} else {
			/**
			 * Added on PHPOE-1762 for PHPOE-1766
			 * "All Grades" option is inserted here instead of inside InstitutionSiteGrades->getInstitutionSiteGradeOptions() 
			 * so as to avoid unadherence of User's Requirements.
			 */
			$gradeOptions[-1] = 'All Grades';
			// sort options by key
			ksort($gradeOptions);
			/**/
		}
		
		$this->_selectedEducationGradeId = $this->queryString('education_grade_id', $gradeOptions);
		$this->advancedSelectOptions($gradeOptions, $this->_selectedEducationGradeId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noSections')),
			'callable' => function($id) use ($Sections, $institutionId, $selectedAcademicPeriodId) {
				/**
				 * If statement added on PHPOE-1762 for PHPOE-1766
				 * If $id is -1, get all sections under the selected academic period
				 */
				if ($id==-1) {
					$query = $Sections->find()
						->join([
							[
								'table' => 'institution_site_section_grades',
								'alias' => 'InstitutionSiteSectionGrades',
								'conditions' => [
									'InstitutionSiteSectionGrades.institution_site_section_id = InstitutionSiteSections.id'
								]
							]
						])
						->where([
							$Sections->aliasField('institution_site_id') => $institutionId,
							$Sections->aliasField('academic_period_id') => $selectedAcademicPeriodId,
						]);
					return $query->count();
				} else {
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

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		/**
		 * Added on PHPOE-1762 (extra feature)
		 */
		$sort = $this->queryString('sort', ['name'=>'name']);
		$direction = $this->queryString('direction', ['asc'=>'asc', 'desc'=>'desc']);
		/**/

		$query
		->find('byGrades')
		->where([$this->aliasField('academic_period_id') => $this->_selectedAcademicPeriodId])
		/**
		 * Added on PHPOE-1762 (extra feature)
		 */
		->order([$this->aliasField('name')=>$direction])
		/**/
		;
	}

    public function findByGrades(Query $query, array $options) {
    	if ($this->_selectedEducationGradeId != -1) {
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
		} else {
	    	return $query
				->join([
					[
						'table' => 'institution_site_section_grades',
						'alias' => 'InstitutionSiteSectionGrades',
						'conditions' => [
							'InstitutionSiteSectionGrades.institution_site_section_id = InstitutionSiteSections.id'
						]
					]
				]);
		}
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
    		$action = $this->ControllerAction->url('view');
    		if (array_key_exists('academic_period_id', $query)) {
	    		unset($action['academic_period_id']);
    		}
    		if (array_key_exists('education_grade_id', $query)) {
	    		unset($action['education_grade_id']);
    		}
    		// $action = $this->ControllerAction->buttons['view']['url'];
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
			'InstitutionSiteSectionStudents.Users.Genders',
			'InstitutionSiteSectionStudents.EducationGrades'
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
    		$action = $this->ControllerAction->url('add');
    		if (array_key_exists('academic_period_id', $query)) {
	    		unset($action['academic_period_id']);
    		}
    		if (array_key_exists('education_grade_id', $query)) {
	    		unset($action['education_grade_id']);
    		}
			$this->controller->redirect($action);
    	}
    	if (array_key_exists('grade_type', $query)) {
    		$this->_selectedGradeType = $query['grade_type'];
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
		$staffOptions = $this->getStaffOptions('add');
		if ($this->_selectedGradeType == 'single') {
	    	if (array_key_exists($this->alias(), $this->request->data)) {
		    	$_data = $this->request->data[$this->alias()];
				$this->_selectedEducationGradeId = $_data['education_grade'];
				$this->_numberOfSections = $_data['number_of_sections'];
				/**
				 * PHPOE-2090, check if selected academic_period_id changes
				 */
				$this->_selectedAcademicPeriodId = $_data['academic_period_id'];
			}

			/**
			 * education_grade field setup
			 * PHPOE-1867 - Changed the population of grades from InstitutionGradesTable
			 */
			$gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptions($this->institutionId, $this->_selectedAcademicPeriodId);
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

			$this->ControllerAction->field('single_grade_field', [
				'type' => 'element', 
				'element' => 'Institution.Sections/single_grade',
				'data' => [	'numberOfSections'=>$this->_numberOfSections,
				 			'staffOptions'=>$staffOptions,
				 			'existedSections'=>$this->getExistedSections(),
				 			'grade'=>$grade	
				]
			]);

			$this->fields['name']['visible'] = false;
			$this->fields['students']['visible'] = false;
			$this->fields['security_user_id']['visible'] = false;
			$this->ControllerAction->setFieldOrder([
				'academic_period_id', 'education_grade', 'institution_site_shift_id', 'section_number', 'number_of_sections', 'single_grade_field'
			]);

    	} else {
			/**
			 * PHPOE-2090, check if selected academic_period_id changes
			 */
	    	if (array_key_exists($this->alias(), $this->request->data)) {
		    	$_data = $this->request->data[$this->alias()];
				$this->_selectedAcademicPeriodId = $_data['academic_period_id'];
			}

			$gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptions($this->institutionId, $this->_selectedAcademicPeriodId, false);
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

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		if ($this->_selectedGradeType == 'single') {
			$process = function ($model, $entity) use ($data) {
				$commonData = $data['InstitutionSiteSections'];
				/**
				 * PHPOE-2090, check if grade is empty as it is mandatory
				 */
				if (!empty($commonData['education_grade'])) {
					foreach($data['MultiClasses'] as $key => $row) {
						$data['MultiClasses'][$key]['institution_site_shift_id'] = $commonData['institution_site_shift_id'];
						$data['MultiClasses'][$key]['institution_site_id'] = $commonData['institution_site_id'];
						$data['MultiClasses'][$key]['academic_period_id'] = $commonData['academic_period_id'];
						$data['MultiClasses'][$key]['institution_site_section_grades'][0] = [
								'education_grade_id' => $commonData['education_grade'],
								'status' => 1
							];
					}
					$classes = $model->newEntities($data['MultiClasses']);
					$error = false;
					foreach ($classes as $key=>$class) {
					    if ($class->errors()) {
					    	$error = $class->errors();
					    	$data['MultiClasses'][$key]['errors'] = $error;
					    }
					}
					/**
					 * attempt to prevent memory leak
					 */
					unset($key);
					unset($class);
					if (!$error) {
						foreach ($classes as $class) {
							$model->save($class);
					    }
						unset($class);
						return true;
					} else {
						$errorMessage='';
						foreach ($error as $key=>$value) {
							$errorMessage .= Inflector::classify($key);
						}
						unset($value);
						$model->log($error, 'debug');
						/**
						 * unset all field validation except for "name" to trigger validation error in ControllerActionComponent
						 */
						foreach ($model->fields as $value) {
							if ($value['field'] != 'name') {
								$model->validator()->remove($value['field']);
							}
						}
						unset($value);
						$model->Alert->error('Institution.'.$model->alias().'.empty'.$errorMessage);
						$model->fields['single_grade_field']['data']['sections'] = $classes;
						$model->request->data['MultiClasses'] = $data['MultiClasses'];
						return false;
					}
				} else {
					$model->Alert->error('Institution.'.$model->alias().'.noGrade');
					return false;
				}
			};
		} else {
			$process = function ($model, $entity) use ($data) {
				$error = false;
				if (array_key_exists('MultiClasses', $data)) {
					$error = [$data['MultiClasses']=>0];
				}
				if (!$error) {
					return $model->save($entity);
				} else {
					$errorMessage='';
					foreach ($error as $key=>$value) {
						$errorMessage .= Inflector::classify($key);
					}
					unset($value);
					$model->log($error, 'debug');
					return false;
				}
			};			
		}
		return $process;
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
        if ($entity->isNew()) {
			/**
			 * using the entity->id (section id), find the list of grades from (institution_site_section_grades) linked to this section
			 */
			$grades = [];
			foreach ($entity->institution_site_section_grades as $grade) {
				$grades[] = $grade->education_grade_id;
			}
			$EducationGrades = TableRegistry::get('Education.EducationGrades');
			/**
			 * from the list of grades, find the list of subjects group by grades in (education_grades_subjects) where visible = 1
			 */
			$educationGradeSubjects = $EducationGrades
					->find()
					->contain(['EducationSubjects' => function($query) use ($grades) {
						return $query
							->join([
								[
									'table' => 'education_grades_subjects',
									'alias' => 'EducationGradesSubjects',
									'conditions' => [
										'EducationGradesSubjects.education_grade_id IN' => $grades,
										'EducationGradesSubjects.education_subject_id = EducationSubjects.id',
										'EducationGradesSubjects.visible' => 1
									]
								]
							]);
					}])
					->where([
						'EducationGrades.id IN' => $grades,
						'EducationGrades.visible' => 1
					])
					->toArray();
			unset($EducationGrades);
			unset($grades);
			
			$educationSubjects = [];
			if (count($educationGradeSubjects)>0) {
				foreach ($educationGradeSubjects as $gradeSubject) {
					foreach ($gradeSubject->education_subjects as $subject) {
						if (!isset($educationSubjects[$subject->id])) {
							$educationSubjects[$subject->id] = [
								'id' => $subject->id,
								'name' => $subject->name
							];
						}
					}
					unset($subject);
				}
				unset($gradeSubject);
			}
			unset($educationGradeSubjects);	

			/**
			 * for each education subjects, find the primary key of institution_site_classes using (entity->academic_period_id and institution_site_id and education_subject_id)
			 */
			$InstitutionSubjects = TableRegistry::get('Institution.InstitutionSiteClasses');
			$institutionSubjects = $InstitutionSubjects->find('list', [
				    'keyField' => 'id',
				    'valueField' => 'education_subject_id'
				])
				->where([
					$InstitutionSubjects->aliasField('academic_period_id') => $entity->academic_period_id,
					$InstitutionSubjects->aliasField('institution_site_id') => $entity->institution_site_id,
					$InstitutionSubjects->aliasField('education_subject_id').' IN' => array_keys($educationSubjects)
				])
				->toArray();
			$institutionSubjectsIds = [];
			foreach ($institutionSubjects as $key => $value) {
				$institutionSubjectsIds[$value][] = $key;
			}
			unset($institutionSubjects);	

			/**
			 * using the list of primary keys, search institution_site_section_classes (InstitutionClassSubjects) to check for existing records
			 * if found, don't insert, 
			 * else create a record in institution_site_classes (InstitutionSubjects)
			 * and link to the subject in institution_site_section_classes (InstitutionClassSubjects) with status 1
			 */
			$InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionSiteSectionClasses');
			$newSchoolSubjects = [];

			foreach ($educationSubjects as $key=>$educationSubject) {
				$getExistingRecord = false;
				if (empty($institutionSubjects)) {
					if (array_key_exists($key, $institutionSubjectsIds)) {
						$getExistingRecord = $InstitutionClassSubjects->find()
							->where([
								$InstitutionClassSubjects->aliasField('institution_site_section_id') => $entity->id,
								$InstitutionClassSubjects->aliasField('institution_site_class_id').' IN' => $institutionSubjectsIds[$key],
							])
							->select(['id'])
							->first();
					}
				}
				if (!$getExistingRecord) {
					$newSchoolSubjects[$key] = [
						'name' => $educationSubject['name'],
						'institution_site_id' => $entity->institution_site_id,
						'education_subject_id' => $educationSubject['id'],
						'academic_period_id' => $entity->academic_period_id,
						'institution_site_section_classes' => [
							[
								'status' => 1,
								'institution_site_section_id' => $entity->id
							]
						]
					];
				}
			}

			if (!empty($newSchoolSubjects)) {
				$newSchoolSubjects = $InstitutionSubjects->newEntities($newSchoolSubjects);
				foreach ($newSchoolSubjects as $subject) {
				    $InstitutionSubjects->save($subject);
				}
				unset($subject);
			}
			unset($newSchoolSubjects);
			unset($InstitutionSubjects);
			unset($InstitutionClassSubjects);
        }
        return true;
	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if ($this->_selectedGradeType != 'single') {
			if (isset($data['InstitutionSiteSections']['institution_site_section_grades']) && count($data['InstitutionSiteSections']['institution_site_section_grades'])>0) {
				foreach($data['InstitutionSiteSections']['institution_site_section_grades'] as $key => $row) {
					$data['InstitutionSiteSections']['institution_site_section_grades'][$key]['status'] = 1;
				}
			} else {
				/**
				 * set institution_site_id to empty to trigger validation error in ControllerActionComponent
				 */
				$data['InstitutionSiteSections']['institution_site_id'] = '';
				$errorMessage = 'Institution.'.$this->alias().'.noGrade';
				$data['MultiClasses'] = $errorMessage;
				$this->Alert->error($errorMessage);
			}
		}
	}

	public function addAfterAction(Event $event, Entity $entity) {
        $this->controller->set('selectedAction', $this->_selectedGradeType);
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

		/**
		 * In students.ctp, we set the student_id as the array keys for easy search and compare.
		 * Assign back original record's id to the new list so as to preserve id numbers.
		 */
		foreach($entity->institution_site_section_students as $key => $record) {
			$k = $record->student_id;
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
					if ($row['status'] == 1 && array_key_exists($row['student_id'], $studentOptions)) {
						$id = $row['student_id'];
						if ($id != 0) {
							$students[] = $this->createVirtualStudentEntity($id, $entity);
						}
						unset($studentOptions[$id]);
					}
				}
			}
			/**
			 * Insert the newly added record into the UI table & unset the record from studentOptions
			 */
			if (array_key_exists('student_id', $this->request->data) && $this->request->data['student_id']>0) {
				$id = $this->request->data['student_id'];
				if ($id != 0) {
					$students[] = $this->createVirtualStudentEntity($id, $entity);
				}
				unset($studentOptions[$id]);
			}
		} else {
			/**
			 * Just unset the record from studentOptions on first page load
			 */
			foreach ($entity->institution_site_section_students as $row) {
				if ($row->status == 1 && array_key_exists($row->student_id, $studentOptions)) {
					unset($studentOptions[$row->student_id]);
				}
			}
		}
		$this->fields['students']['data']['students'] = $students;
		$this->fields['students']['data']['studentOptions'] = $studentOptions;

		$gradeOptions = $this->getSectionGradeOptions($entity);
		$this->fields['students']['data']['gradeOptions'] = $gradeOptions;
	}

	public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions) {
		$record = $this->find()->contain([
			'InstitutionSiteClasses.InstitutionSiteClassStudents', 
			'InstitutionSiteClasses.InstitutionSiteSections', 
			'InstitutionSiteSectionStudents.Users'
		])->where([$this->aliasField('id')=>$entity->id])->first();
		$classes = [];
		foreach ($record->institution_site_classes as $class) {
			$students = [];
			foreach($record->institution_site_section_students as $sectionStudent) {
				$students[$sectionStudent->user->id] = $this->InstitutionSiteClasses->createVirtualEntity($sectionStudent->user->id, $class, 'students');
			}
			if (count($class->institution_site_class_students)>0) {
				foreach($class->institution_site_class_students as $classStudent) {
					if (!isset($students[$classStudent->student_id])) {
						$classStudent->status=0;
						$students[$classStudent->student_id] = $classStudent;
					}
				}
			}
			$class->institution_site_class_students = $students;
			$this->InstitutionSiteClasses->save($class);
		}
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
		$periodOption = ['' => '-- Select Period --'];
		$academicPeriodOptions = $this->AcademicPeriods->getlist();
		$academicPeriodOptions = $periodOption + $academicPeriodOptions;
		if ($action == 'edit') {
		
			$attr['type'] = 'readonly';
			if ($this->_selectedAcademicPeriodId > -1) {
				$attr['attr']['value'] = $this->AcademicPeriods->get($this->_selectedAcademicPeriodId)->name;
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
				$attr['options'] = $this->getStaffOptions('edit');
			}

		} elseif ($action == 'add') {

			// $attr['type'] = 'select';

		} elseif (in_array($action, ['view', 'index'])) {

			// $attr['type'] = 'select';
			if ($this->_selectedAcademicPeriodId > -1) {
				$attr['options'] = $this->getStaffOptions('view');
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

	/**
	 * [getStudentsOptions description]
	 * @param  [type] $sectionEntity [description]
	 * @return [type]                [description]
	 */
	protected function getStudentsOptions($sectionEntity) {
		
		$academicPeriodObj = $this->AcademicPeriods->get($this->_selectedAcademicPeriodId);
		$sectionGradeObjects = $sectionEntity->institution_site_section_grades;
		$sectionGrades = [];
		foreach ($sectionGradeObjects as $key=>$value) {
			$sectionGrades[] = $value->education_grade_id;
		}

		/**
		 * Modified this query in PHPOE-1780. Use PeriodBehavior which is loaded InstitutionSiteStudents, by adding ->find('AcademicPeriod', ['academic_period_id'=> $this->_selectedAcademicPeriodId])
		 * This is inline with how InstitutionSiteClassesTable populate getStudentOptions.
		 */
		$students = $this->Institutions->Students;
		$query = $students
			->find('all')
			->find('AcademicPeriod', ['academic_period_id' => $this->_selectedAcademicPeriodId])
			->contain(['Users'])
			->where([
				$students->aliasField('institution_id') => $this->institutionId
			])
			->toArray();
		$studentOptions = [$this->getMessage('Users.select_student')];
		foreach ($query as $skey => $obj) {
			/**
			 * Modified this filter in PHPOE-1799.
			 * Use institution_students table through $this->Institutions->Students where Students being the table alias.
			 */
			if (in_array($obj->education_grade_id, $sectionGrades)) {
				if (isset($obj->user)) {
					$studentOptions[$obj->user->id] = $obj->user->name_with_id;
				} else {
					$this->log('Data corrupted with no security user for student: '. $obj->id, 'debug');
				}
			}
		}
		$studentOptions = $this->attachSectionInfo($sectionEntity->id, $studentOptions);
		return $studentOptions;
	}

	public function attachSectionInfo($id, $studentOptions) {
		$query = $this->InstitutionSiteSectionStudents->find()
					->contain(['InstitutionSiteSections'])
					->where([
						$this->aliasField('institution_site_id') => $this->institutionId,
						$this->aliasField('academic_period_id') => $this->_selectedAcademicPeriodId,
					])
					->where([
							$this->InstitutionSiteSectionStudents->aliasField('student_id').' IN' => array_keys($studentOptions),
							$this->InstitutionSiteSectionStudents->aliasField('status') => 1
						]);
		$sectionsWithStudents = $query->toArray();

		foreach($sectionsWithStudents as $student) {
			if($student->institution_site_section_id != $id) {
				if (!isset($studentOptions[$student->institution_site_section->name])) {
					$studentOptions[$student->institution_site_section->name] = ['text' => 'Section '.$student->institution_site_section->name, 'options' => [], 'disabled' => true];
				}
				$studentOptions[$student->institution_site_section->name]['options'][] = ['value' => $student->student_id, 'text' => $studentOptions[$student->student_id]];
				unset($studentOptions[$student->student_id]);
			}
		}
		return $studentOptions;
	}

	protected function getStaffOptions($action='edit') {
		
		$academicPeriodObj = $this->AcademicPeriods->get($this->_selectedAcademicPeriodId);
		$startDate = $this->AcademicPeriods->getDate($academicPeriodObj->start_date);
        $endDate = $this->AcademicPeriods->getDate($academicPeriodObj->end_date);

        $Staff = $this->Institutions->InstitutionSiteStaff;
		$query = $Staff->find('all')
						->find('withBelongsTo')
						->find('byPositions', ['Institutions.id' => $this->institutionId, 'type' => 1]) // refer to OptionsTrait for type options
						->find('byInstitution', ['Institutions.id'=>$this->institutionId])
						->find('AcademicPeriod', ['academic_period_id'=>$academicPeriodObj->id])
						;

		if (in_array($action, ['edit', 'add'])) {
			$options = [0=>'-- Select Teacher or Leave Blank --'];
		} else {
			$options = [0=>'No Teacher Assigned'];
		}
		
		foreach ($query->toArray() as $key => $value) {
			if ($value->has('user')) {
				$options[$value->user->id] = $value->user->name;
			}
		}
		return $options;
	}

	private function numberOfSectionsOptions() {
		$total = 10;
		$options = [];
		for($i=1; $i<=$total; $i++){
			$options[$i] = $i;
		}
		
		return $options;
	}
	
	private function getExistedSections() {
		$sectionsByGrade = $this->InstitutionSiteSectionGrades
			->find('list', [
				'keyField'=>'id',
				'valueField'=>'institution_site_section_id'
			])
			->where([$this->InstitutionSiteSectionGrades->aliasField('education_grade_id') => $this->_selectedEducationGradeId])
			->toArray();

		$data = $this->find('list', [
				'keyField' => 'id',
			    'valueField' => 'name'
			])
			->where([
				/**
				 * If section_number is null, it is considered as a multi-grade section
				 */
				$this->aliasField('section_number').' IS NOT NULL',
				$this->aliasField('institution_site_id') => $this->institutionId,
				$this->aliasField('academic_period_id') => $this->_selectedAcademicPeriodId,
				$this->aliasField('id').' IN' => $sectionsByGrade,
			])
			->toArray()
			;
		return $data;
	}

	protected function createVirtualStudentEntity($id, $entity) {
		$userData = $this->Institutions->Students->find()
			->contain(['Users'=>['Genders']])
			->where(['student_id'=>$id])
			->first();

		$data = [
			'id'=>$this->getExistingRecordId($id, $entity),
			'student_id'=>$id,
			'institution_site_section_id'=>$entity->id,
			'education_grade_id'=>0,
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
			if ($student->student_id == $securityId) {
				$id = $student->id;
			}
		}
		return $id;
	}

	private function getAcademicPeriodOptions() {
		$InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
		$conditions = array(
			'InstitutionGrades.institution_site_id' => $this->institutionId
		);
		$list = $InstitutionGrades->getAcademicPeriodOptions($this->Alert, $conditions);
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
		$multiGradeOptions = array(
			'fields' => array('InstitutionSiteSections.id', 'InstitutionSiteSections.name'),
			'conditions' => array(
				'InstitutionSiteSections.academic_period_id' => $academicPeriodId,
				'InstitutionSiteSections.institution_site_id' => $institutionId
			),
			'order' => array('InstitutionSiteSections.name')
		);

		if($gradeId != false) {
			$multiGradeOptions['join'] = array(
				array(
					'table' => 'institution_site_section_grades',
					'alias' => 'InstitutionSiteSectionGrades',
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
	