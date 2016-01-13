<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Collection\Collection;

use App\Model\Table\AppTable;

class InstitutionSectionsTable extends AppTable {
	private $institutionId = 0;
	private $_numberOfSections = 1;
	private $_selectedGradeType = 'single';
	private $_selectedAcademicPeriodId = -1;
	private $_selectedEducationGradeId = 0;

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', 		['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Staff', 					['className' => 'User.Users', 						'foreignKey' => 'staff_id']);
		$this->belongsTo('InstitutionShifts',		['className' => 'Institution.InstitutionShifts', 'foreignKey' => 'institution_shift_id']);
		$this->belongsTo('Institutions', 			['className' => 'Institution.Institutions', 		'foreignKey' => 'institution_id']);

		$this->hasMany('InstitutionSectionGrades', 		['className' => 'Institution.InstitutionSectionGrades', 'dependent' => true]);
		$this->hasMany('InstitutionSectionStudents', 	['className' => 'Institution.InstitutionSectionStudents', 'dependent' => true]);

		$this->belongsToMany('InstitutionClasses', [
			'className' => 'Institution.InstitutionClasses',
			'joinTable' => 'institution_section_classes',
			'foreignKey' => 'institution_section_id',
			'targetForeignKey' => 'institution_class_id'
		]);

		/**
		 * Shortcuts
		 */
		$this->InstitutionGrades = $this->Institutions->InstitutionGrades;

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
				$model->aliasField('institution_id') => $globalData['data']['institution_id'],
				$model->aliasField('name') => $field,
			])
			->toArray();
		if (!empty($exists)) {
			foreach ($exists as $key => $value) {
				if (array_key_exists('id', $data) && $value->id == $data['id']) {
					// if editing an existing value
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
		$this->institutionId = $this->Session->read('Institution.Institutions.id');
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
		$this->ControllerAction->field('institution_shift_id', ['type' => 'select', 'visible' => ['view'=>true, 'edit'=>true]]);

		$this->ControllerAction->field('staff_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);

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
			'name', 'staff_id', 'male_students', 'female_students', 'classes',
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

		$academicPeriodOptions = $this->AcademicPeriods->getList();

		$institutionId = $this->institutionId;
		$this->_selectedAcademicPeriodId = $this->queryString('academic_period_id', $academicPeriodOptions);
		$this->advancedSelectOptions($academicPeriodOptions, $this->_selectedAcademicPeriodId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
			'callable' => function($id) use ($Sections, $institutionId) {
				return $Sections->findByInstitutionIdAndAcademicPeriodId($institutionId, $id)->count();
			}
		]);
		$gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptions($this->institutionId, $this->_selectedAcademicPeriodId);
		$selectedAcademicPeriodId = $this->_selectedAcademicPeriodId;
		if (!empty($gradeOptions)) {
			/**
			 * Added on PHPOE-1762 for PHPOE-1766
			 * "All Grades" option is inserted here instead of inside InstitutionGrades->getInstitutionGradeOptions() 
			 * so as to avoid unadherence of User's Requirements.
			 */
			$gradeOptions[-1] = 'All Grades';
			// sort options by key
			ksort($gradeOptions);
			/**/
		}
		
		$this->_selectedEducationGradeId = $this->queryString('education_grade_id', $gradeOptions);
		$this->advancedSelectOptions($gradeOptions, $this->_selectedEducationGradeId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
			'callable' => function($id) use ($Sections, $institutionId, $selectedAcademicPeriodId) {
				/**
				 * If statement added on PHPOE-1762 for PHPOE-1766
				 * If $id is -1, get all sections under the selected academic period
				 */
				if ($id==-1) {
					$query = $Sections->find()
						->join([
							[
								'table' => 'institution_section_grades',
								'alias' => 'InstitutionSectionGrades',
								'conditions' => [
									'InstitutionSectionGrades.institution_section_id = InstitutionSections.id'
								]
							]
						])
						->where([
							$Sections->aliasField('institution_id') => $institutionId,
							$Sections->aliasField('academic_period_id') => $selectedAcademicPeriodId,
						]);
					return $query->count();
				} else {
					$query = $Sections->find()
						->join([
							[
								'table' => 'institution_section_grades',
								'alias' => 'InstitutionSectionGrades',
								'conditions' => [
									'InstitutionSectionGrades.institution_section_id = InstitutionSections.id',
									'InstitutionSectionGrades.education_grade_id' => $id
								]
							]
						])
						->where([
							$Sections->aliasField('institution_id') => $institutionId,
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
						'table' => 'institution_section_grades',
						'alias' => 'InstitutionSectionGrades',
						'conditions' => [
							'InstitutionSectionGrades.institution_section_id = InstitutionSections.id',
							'InstitutionSectionGrades.education_grade_id' => $this->_selectedEducationGradeId
						]
					]
				]);
		} else {
	    	return $query
				->join([
					[
						'table' => 'institution_section_grades',
						'alias' => 'InstitutionSectionGrades',
						'conditions' => [
							'InstitutionSectionGrades.institution_section_id = InstitutionSections.id'
						]
					]
				])
				->group(['InstitutionSectionGrades.institution_section_id'])
				;
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
			'academic_period_id', 'name', 'institution_shift_id', 'education_grades', 'staff_id', 'students'
		]);

	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain([
			'AcademicPeriods',
			'InstitutionShifts',
			'Staff',
			'InstitutionSectionGrades.EducationGrades',
			'InstitutionSectionStudents.Users.Genders',
			'InstitutionSectionStudents.EducationGrades'
		]);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->fields['students']['data']['students'] = $entity->institution_section_students;
		$this->fields['education_grades']['data']['grades'] = $entity->institution_section_grades;

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
			$gradeOptions = [];
			if (!empty($this->_selectedAcademicPeriodId)) {
				$gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptions($this->institutionId, $this->_selectedAcademicPeriodId);
			}
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
			if ($this->InstitutionSectionGrades->EducationGrades->exists(['id' => $this->_selectedEducationGradeId])) {
				$grade = $this->InstitutionSectionGrades->EducationGrades->get($this->_selectedEducationGradeId, [
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
			$this->fields['staff_id']['visible'] = false;
			$this->fields['staff_id']['type'] = 'hidden';
			$this->ControllerAction->setFieldOrder([
				'academic_period_id', 'education_grade', 'institution_shift_id', 'section_number', 'number_of_sections', 'single_grade_field'
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
			$this->fields['staff_id']['options'] = $staffOptions;
			$this->fields['students']['visible'] = false;
			$this->ControllerAction->setFieldOrder([
				'academic_period_id', 'name', 'institution_shift_id', 'staff_id', 'multi_grade_field'
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
				$commonData = $data['InstitutionSections'];
				/**
				 * PHPOE-2090, check if grade is empty as it is mandatory
				 */
				if (!empty($commonData['education_grade'])) {
					foreach($data['MultiClasses'] as $key => $row) {
						$data['MultiClasses'][$key]['institution_shift_id'] = $commonData['institution_shift_id'];
						$data['MultiClasses'][$key]['institution_id'] = $commonData['institution_id'];
						$data['MultiClasses'][$key]['academic_period_id'] = $commonData['academic_period_id'];
						$data['MultiClasses'][$key]['institution_section_grades'][0] = [
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
						if ($errorMessage != 'AcademicPeriodId') {
							$model->Alert->error('Institution.'.$model->alias().'.empty'.$errorMessage, ['reset' => true]);
						}
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
			 * using the entity->id (section id), find the list of grades from (institution_section_grades) linked to this section
			 */
			$grades = [];
			foreach ($entity->institution_section_grades as $grade) {
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
			 * for each education subjects, find the primary key of institution_classes using (entity->academic_period_id and institution_id and education_subject_id)
			 */
			$InstitutionSubjects = TableRegistry::get('Institution.InstitutionClasses');
			$institutionSubjects = $InstitutionSubjects->find('list', [
				    'keyField' => 'id',
				    'valueField' => 'education_subject_id'
				])
				->where([
					$InstitutionSubjects->aliasField('academic_period_id') => $entity->academic_period_id,
					$InstitutionSubjects->aliasField('institution_id') => $entity->institution_id,
					$InstitutionSubjects->aliasField('education_subject_id').' IN' => array_keys($educationSubjects)
				])
				->toArray();
			$institutionSubjectsIds = [];
			foreach ($institutionSubjects as $key => $value) {
				$institutionSubjectsIds[$value][] = $key;
			}
			unset($institutionSubjects);	

			/**
			 * using the list of primary keys, search institution_section_classes (InstitutionClassSubjects) to check for existing records
			 * if found, don't insert, 
			 * else create a record in institution_classes (InstitutionSubjects)
			 * and link to the subject in institution_section_classes (InstitutionClassSubjects) with status 1
			 */
			$InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionSectionClasses');
			$newSchoolSubjects = [];

			foreach ($educationSubjects as $key=>$educationSubject) {
				$getExistingRecord = false;
				if (empty($institutionSubjects)) {
					if (array_key_exists($key, $institutionSubjectsIds)) {
						$getExistingRecord = $InstitutionClassSubjects->find()
							->where([
								$InstitutionClassSubjects->aliasField('institution_section_id') => $entity->id,
								$InstitutionClassSubjects->aliasField('institution_class_id').' IN' => $institutionSubjectsIds[$key],
							])
							->select(['id'])
							->first();
					}
				}
				if (!$getExistingRecord) {
					$newSchoolSubjects[$key] = [
						'name' => $educationSubject['name'],
						'institution_id' => $entity->institution_id,
						'education_subject_id' => $educationSubject['id'],
						'academic_period_id' => $entity->academic_period_id,
						'institution_section_classes' => [
							[
								'status' => 1,
								'institution_section_id' => $entity->id
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
			if (isset($data['InstitutionSections']['institution_section_grades']) && count($data['InstitutionSections']['institution_section_grades'])>0) {
				foreach($data['InstitutionSections']['institution_section_grades'] as $key => $row) {
					$data['InstitutionSections']['institution_section_grades'][$key]['status'] = 1;
				}
			} else {
				/**
				 * set institution_id to empty to trigger validation error in ControllerActionComponent
				 */
				$data['InstitutionSections']['institution_id'] = '';
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
			'academic_period_id', 'name', 'institution_shift_id', 'staff_id', 'students',
		]);
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		/**
		 * Unable to utilise updateAll for this scenario.
		 * Only new student records will be saved as status=1 at the later part of this scope.
		 * Existitng records which is not removed from the UI list, will remain as status=0 instead of 1.
		 */
		// $this->InstitutionSectionStudents->updateAll(['status'=>0], ['institution_section_id' => $entity->id]);

		/**
		 * In students.ctp, we set the student_id as the array keys for easy search and compare.
		 * Assign back original record's id to the new list so as to preserve id numbers.
		 */
		foreach($entity->institution_section_students as $key => $record) {
			$k = $record->student_id;
			if (array_key_exists('institution_section_students', $data[$this->alias()])) {
				if (!array_key_exists($k, $data[$this->alias()]['institution_section_students'])) {			
					// PHPOE-2338 - status no longer used, record will be deleted instead
				} else {
					$data[$this->alias()]['institution_section_students'][$k]['id'] = $record->id;
				}
			} else {
				// PHPOE-2338 - status no longer used, record will be deleted instead
			}
		}
	}

	public function editAfterAction(Event $event, Entity $entity) {

		$students = $entity->institution_section_students;
		$studentOptions = $this->getStudentsOptions($entity);
		/**
		 * Check if the request is a page reload
		 */
		if (count($this->request->data)>0 && $this->request->data['submit']=='add') {
			// clear institution_section_students list grab from db
			$existingStudents = $students;
			$students = [];
			/**
			 * Populate records in the UI table & unset the record from studentOptions
			 */
			if (array_key_exists('institution_section_students', $this->request->data[$this->alias()])) {
				foreach ($this->request->data[$this->alias()]['institution_section_students'] as $row) {
					if (array_key_exists($row['student_id'], $studentOptions)) {
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
			foreach ($entity->institution_section_students as $row) {
				if (array_key_exists($row->student_id, $studentOptions)) {
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
			'InstitutionClasses.InstitutionClassStudents', 
			'InstitutionClasses.InstitutionSections', 
			'InstitutionSectionStudents.Users'
		])->where([$this->aliasField('id')=>$entity->id])->first();
		
		// finding removed student ids
		$currentInstitutionSectionStudents = $entity->institution_section_students;
		$currentStudentIds = [];
		foreach ($currentInstitutionSectionStudents as $key => $value) {
			$currentStudentIds[] = $value->student_id;
		}
		$originalInstitutionSectionStudents = $entity->getOriginal('institution_section_students');
		$originalStudentIds = [];
		foreach ($originalInstitutionSectionStudents as $key => $value) {
			$originalStudentIds[] = $value->student_id;
		}
		$removedStudentIds = array_diff($originalStudentIds, $currentStudentIds);

		$classes = [];
		foreach ($record->institution_classes as $class) {
			$students = [];
			foreach($record->institution_section_students as $sectionStudent) {
				if (!$sectionStudent->has('user')) continue;
				$requiredData = (array_key_exists($sectionStudent->user->id, $requestData[$this->alias()]['institution_section_students']))? $requestData[$this->alias()]['institution_section_students'][$sectionStudent->user->id]: null;
				if (in_array($sectionStudent->user->id, $removedStudentIds)) {
					$requiredData['status'] = 0;
				}
				$students[$sectionStudent->user->id] = $this->InstitutionClasses->createVirtualEntity($sectionStudent->user->id, $class, 'students', $requiredData);
			}
			if (count($class->institution_class_students)>0) {
				foreach($class->institution_class_students as $classStudent) {
					if (!isset($students[$classStudent->student_id])) {
						$classStudent->status=0;
						$students[$classStudent->student_id] = $classStudent;
					}
				}
			}

			$class->institution_class_students = $students;
			$this->InstitutionClasses->save($class);
		}

		if (!empty($removedStudentIds)) {
			// 'deleteAll will not trigger beforeDelete/afterDelete events. If you need those first load a collection of records and delete them.'
			foreach ($removedStudentIds as $key => $value) {
				$deleteSectionStudent = $this->InstitutionSectionStudents->find()
					->where([
						$this->InstitutionSectionStudents->aliasField('institution_section_id') => $entity->id,
						$this->InstitutionSectionStudents->aliasField('student_id').' IN ' => $removedStudentIds
					])
					->toArray()
					;
				foreach ($deleteSectionStudent as $key => $value) {
					$this->InstitutionSectionStudents->delete($value);
				}
			}
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
	 * institution_shift_id field setup
	 */
	public function onUpdateFieldInstitutionShiftId(Event $event, array $attr, $action, $request) {
		
		if ($action == 'edit' || $action == 'add') {

			if ($this->_selectedAcademicPeriodId > -1) {
				$this->InstitutionShifts->createInstitutionDefaultShift($this->institutionId, $this->_selectedAcademicPeriodId);
				$shiftOptions = $this->InstitutionShifts->getShiftOptions($this->institutionId, $this->_selectedAcademicPeriodId);
			} else {
				$shiftOptions = [];
			}
			$attr['options'] = $shiftOptions;

		}

		return $attr;
	}

	/**
	 * staff_id field setup
	 */
	public function onUpdateFieldStaffId(Event $event, array $attr, $action, $request) {
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
		$Grade = $this->InstitutionSectionGrades;
		$gradeOptions = $Grade->find()
							->contain('EducationGrades')
							->where([
								$Grade->aliasField('institution_section_id') => $entity->id,
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
		$sectionGradeObjects = $sectionEntity->institution_section_grades;
		$sectionGrades = [];
		foreach ($sectionGradeObjects as $key=>$value) {
			$sectionGrades[] = $value->education_grade_id;
		}

		/**
		 * Modified this query in PHPOE-1780. Use PeriodBehavior which is loaded InstitutionStudents, by adding ->find('AcademicPeriod', ['academic_period_id'=> $this->_selectedAcademicPeriodId])
		 * This is inline with how InstitutionClassesTable populate getStudentOptions.
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
		$query = $this->InstitutionSectionStudents->find()
					->contain(['InstitutionSections'])
					->where([
						$this->aliasField('institution_id') => $this->institutionId,
						$this->aliasField('academic_period_id') => $this->_selectedAcademicPeriodId,
					])
					->where([
							$this->InstitutionSectionStudents->aliasField('student_id').' IN' => array_keys($studentOptions)
						]);
		$sectionsWithStudents = $query->toArray();

		foreach($sectionsWithStudents as $student) {
			if($student->institution_section_id != $id) {
				if (!isset($studentOptions[$student->institution_section->name])) {
					$studentOptions[$student->institution_section->name] = ['text' => 'Section '.$student->institution_section->name, 'options' => [], 'disabled' => true];
				}
				$studentOptions[$student->institution_section->name]['options'][] = ['value' => $student->student_id, 'text' => $studentOptions[$student->student_id]];
				unset($studentOptions[$student->student_id]);
			}
		}
		return $studentOptions;
	}

	protected function getStaffOptions($action='edit') {
		if (in_array($action, ['edit', 'add'])) {
			$options = [0=>'-- Select Teacher or Leave Blank --'];
		} else {
			$options = [0=>'No Teacher Assigned'];
		}

		if (!empty($this->_selectedAcademicPeriodId)) {

			$academicPeriodObj = $this->AcademicPeriods->get($this->_selectedAcademicPeriodId);
			$startDate = $this->AcademicPeriods->getDate($academicPeriodObj->start_date);
	        $endDate = $this->AcademicPeriods->getDate($academicPeriodObj->end_date);

	        $Staff = $this->Institutions->Staff;
			$query = $Staff->find('all')
							->find('withBelongsTo')
							->find('byPositions', ['Institutions.id' => $this->institutionId, 'type' => 1]) // refer to OptionsTrait for type options
							->find('byInstitution', ['Institutions.id'=>$this->institutionId])
							->find('AcademicPeriod', ['academic_period_id'=>$academicPeriodObj->id])
							;

			foreach ($query->toArray() as $key => $value) {
				if ($value->has('user')) {
					$options[$value->user->id] = $value->user->name;
				}
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
		$sectionsByGrade = $this->InstitutionSectionGrades
			->find('list', [
				'keyField'=>'id',
				'valueField'=>'institution_section_id'
			])
			->where([$this->InstitutionSectionGrades->aliasField('education_grade_id') => $this->_selectedEducationGradeId])
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
				$this->aliasField('institution_id') => $this->institutionId,
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
			'institution_section_id'=>$entity->id,
			'education_grade_id'=>0,
			'user'=>[]
		];
		$student = $this->InstitutionSectionStudents->newEntity();
		$student = $this->InstitutionSectionStudents->patchEntity($student, $data);
		$student->user = $userData->user;
		return $student;
	}

	protected function getExistingRecordId($securityId, $entity) {
		$id = Text::uuid();
		foreach ($entity->institution_section_students as $student) {
			if ($student->student_id == $securityId) {
				$id = $student->id;
			}
		}
		return $id;
	}

	private function getAcademicPeriodOptions() {
		$InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
		$conditions = array(
			'InstitutionGrades.institution_id' => $this->institutionId
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
	 * Used by Institution/UserBehavior && Institution/InstitutionStudentsTable
	 * @param  [integer]  $academicPeriodId [description]
	 * @param  [integer]  $institutionId    [description]
	 * @param  boolean $gradeId          [description]
	 * @return [type]                    [description]
	 */
	public function getSectionOptions($academicPeriodId, $institutionId, $gradeId=false) {
		$multiGradeOptions = array(
			'fields' => array('InstitutionSections.id', 'InstitutionSections.name'),
			'conditions' => array(
				'InstitutionSections.academic_period_id' => $academicPeriodId,
				'InstitutionSections.institution_id' => $institutionId
			),
			'order' => array('InstitutionSections.name')
		);

		if($gradeId != false) {
			$multiGradeOptions['join'] = array(
				array(
					'table' => 'institution_section_grades',
					'alias' => 'InstitutionSectionGrades',
					'conditions' => array(
						'InstitutionSectionGrades.institution_section_id = InstitutionSections.id',
						'InstitutionSectionGrades.education_grade_id = ' . $gradeId,
						'InstitutionSectionGrades.status = 1'
					)
				)
			);
			$multiGradeOptions['group'] = array('InstitutionSections.id');
		}

		$multiGradeData = $this->find('list', $multiGradeOptions);
		return $multiGradeData->toArray();
	}
}
	