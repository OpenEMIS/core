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

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

class InstitutionClassesTable extends ControllerActionTable {
	use MessagesTrait;

	private $institutionId = 0;
	private $_numberOfClasses = 1;
	private $_selectedGradeType = 'single';
	private $_selectedAcademicPeriodId = -1;
	private $_selectedEducationGradeId = 0;

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', 		['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Staff', 					['className' => 'User.Users', 						'foreignKey' => 'staff_id']);
		$this->belongsTo('InstitutionShifts',		['className' => 'Institution.InstitutionShifts', 	'foreignKey' => 'institution_shift_id']);
		$this->belongsTo('Institutions', 			['className' => 'Institution.Institutions', 		'foreignKey' => 'institution_id']);

		$this->hasMany('InstitutionClassGrades', 	['className' => 'Institution.InstitutionClassGrades', 'dependent' => true]);
		$this->hasMany('InstitutionClassStudents', 	['className' => 'Institution.InstitutionClassStudents', 'dependent' => true]);

		$this->belongsToMany('InstitutionSubjects', [
			'className' => 'Institution.InstitutionSubjects',
			'joinTable' => 'institution_class_subjects',
			'foreignKey' => 'institution_class_id',
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
	        		// 'message' => 'Class name has to be unique'
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

	public function beforeAction(Event $event, ArrayObject $extra) {
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

		$this->field('class_number', ['visible' => false]);
		$this->field('modified_user_id', ['visible' => false]);
		$this->field('modified', ['visible' => false]);
		$this->field('created_user_id', ['visible' => false]);
		$this->field('created', ['visible' => false]);

		$this->field('academic_period_id', ['type' => 'select', 'visible' => ['view'=>true, 'edit'=>true]]);
		$this->field('institution_shift_id', ['type' => 'select', 'visible' => ['view'=>true, 'edit'=>true]]);

		$this->field('staff_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);

		$this->field('male_students', ['type' => 'integer', 'visible' => ['index'=>true]]);
		$this->field('female_students', ['type' => 'integer', 'visible' => ['index'=>true]]);
		$this->field('subjects', ['override' => true, 'type' => 'integer', 'visible' => ['index'=>true]]);

		$this->field('students', [
			'label' => '',
			'override' => true,
			'type' => 'element',
			'element' => 'Institution.Classes/students',
			'data' => [	
				'students'=>[],
				'studentOptions'=>[]
			],
			'visible' => ['view'=>true, 'edit'=>true]
			// 'visible' => false
		]);
		$this->field('education_grades', [
			'type' => 'element',
			'element' => 'Institution.Classes/multi_grade',
			'data' => [	
				'grades'=>[]
			],
			'visible' => ['view'=>true]
		]);

		$this->setFieldOrder([
			'name', 'staff_id', 'male_students', 'female_students', 'subjects',
		]);

	}


/******************************************************************************************************************
**
** delete action methods
**
******************************************************************************************************************/
	public function onBeforeDelete(Event $event, ArrayObject $deleteOptions, $id, ArrayObject $extra) {
		$Students = $this->InstitutionClassStudents;
		$conditions = [$Students->aliasField($Students->foreignKey()) => $id];
		if ($Students->exists($conditions)) {
			$this->Alert->warning($this->aliasField('stopDeleteWhenStudentExists'));
			$event->stopPropagation();
			return $this->controller->redirect($this->url('index'));
		}
	}

/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
    public function indexBeforeAction(Event $event, ArrayObject $extra) {
		$query = $this->request->query;
    	if (array_key_exists('grade_type', $query)) {
			$action = $this->url('index');//$this->buttons['index']['url'];
			unset($action['grade_type']);
			$this->controller->redirect($action);
    	}

		$Classes = $this;

		$academicPeriodOptions = $this->AcademicPeriods->getList();

		$institutionId = $this->institutionId;
		$this->_selectedAcademicPeriodId = $this->queryString('academic_period_id', $academicPeriodOptions);
		$this->advancedSelectOptions($academicPeriodOptions, $this->_selectedAcademicPeriodId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
			'callable' => function($id) use ($Classes, $institutionId) {
				return $Classes->findByInstitutionIdAndAcademicPeriodId($institutionId, $id)->count();
			}
		]);
		$gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptionsForIndex($this->institutionId, $this->_selectedAcademicPeriodId);
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
			'callable' => function($id) use ($Classes, $institutionId, $selectedAcademicPeriodId) {
				/**
				 * If statement added on PHPOE-1762 for PHPOE-1766
				 * If $id is -1, get all classes under the selected academic period
				 */
				if ($id==-1) {
					$query = $Classes->find()
						->join([
							[
								'table' => 'institution_class_grades',
								'alias' => 'InstitutionClassGrades',
								'conditions' => [
									'InstitutionClassGrades.institution_class_id = InstitutionClasses.id'
								]
							]
						])
						->where([
							$Classes->aliasField('institution_id') => $institutionId,
							$Classes->aliasField('academic_period_id') => $selectedAcademicPeriodId,
						]);
					return $query->count();
				} else {
					$query = $Classes->find()
						->join([
							[
								'table' => 'institution_class_grades',
								'alias' => 'InstitutionClassGrades',
								'conditions' => [
									'InstitutionClassGrades.institution_class_id = InstitutionClasses.id',
									'InstitutionClassGrades.education_grade_id' => $id
								]
							]
						])
						->where([
							$Classes->aliasField('institution_id') => $institutionId,
							$Classes->aliasField('academic_period_id') => $selectedAcademicPeriodId,
						]);
					return $query->count();
				}
			}
		]);

		$extra['elements']['control'] = [
			'name' => 'Institution.Classes/controls', 
			'data' => [
            	'academicPeriodOptions'=>$academicPeriodOptions, 
            	'selectedAcademicPeriod'=>$this->_selectedAcademicPeriodId, 
            	'gradeOptions'=>$gradeOptions, 
            	'selectedGrade'=>$this->_selectedEducationGradeId, 
			],
			'options' => [],
			'order' => 3
		];
    }

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
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
						'table' => 'institution_class_grades',
						'alias' => 'InstitutionClassGrades',
						'conditions' => [
							'InstitutionClassGrades.institution_class_id = InstitutionClasses.id',
							'InstitutionClassGrades.education_grade_id' => $this->_selectedEducationGradeId
						]
					]
				]);
		} else {
	    	return $query
				->join([
					[
						'table' => 'institution_class_grades',
						'alias' => 'InstitutionClassGrades',
						'conditions' => [
							'InstitutionClassGrades.institution_class_id = InstitutionClasses.id'
						]
					]
				])
				->group(['InstitutionClassGrades.institution_class_id'])
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
				'action' => 'Classes'
			]);
		}

		$query = $this->request->query;
    	if (array_key_exists('academic_period_id', $query) || array_key_exists('education_grade_id', $query)) {
    		$action = $this->url('view');
    		if (array_key_exists('academic_period_id', $query)) {
	    		unset($action['academic_period_id']);
    		}
    		if (array_key_exists('education_grade_id', $query)) {
	    		unset($action['education_grade_id']);
    		}
    		// $action = $this->buttons['view']['url'];
			$this->controller->redirect($action);
    	}

		$this->setFieldOrder([
			'academic_period_id', 'name', 'institution_shift_id', 'education_grades', 'staff_id', 'students'
		]);

	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain([
			'InstitutionClassStudents.StudentStatuses',
			'AcademicPeriods',
			'InstitutionShifts',
			'Staff',
			'InstitutionClassGrades.EducationGrades',
			'InstitutionClassStudents.Users.Genders',
			'InstitutionClassStudents.EducationGrades'
		]);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->fields['students']['data']['students'] = $entity->institution_class_students;
		$this->fields['education_grades']['data']['grades'] = $entity->institution_class_grades;

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
    		$action = $this->url('add');
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
				'action' => 'Classes'
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
				$this->_numberOfClasses = $_data['number_of_classes'];
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
			$this->field('education_grade', [
				'type' => 'select',
				'options' => $gradeOptions,
				'onChangeReload' => true,
				'attr' => [
 					'empty' => ((empty($gradeOptions)) ? $this->Alert->getMessage($this->aliasField('education_grade_options_empty')) : '')
				]
			]);

			$numberOfClassesOptions = $this->numberOfClassesOptions();
			$this->field('number_of_classes', [
				'type' => 'select', 
				'options' => $numberOfClassesOptions,
				'onChangeReload' => true
			]);

			$grade = [];
			if ($this->InstitutionClassGrades->EducationGrades->exists(['id' => $this->_selectedEducationGradeId])) {
				$grade = $this->InstitutionClassGrades->EducationGrades->get($this->_selectedEducationGradeId, [
				    'contain' => ['EducationProgrammes']
				])->toArray();
			}

			$this->field('single_grade_field', [
				'type' => 'element', 
				'element' => 'Institution.Classes/single_grade',
				'data' => [	'numberOfClasses'=>$this->_numberOfClasses,
				 			'staffOptions'=>$staffOptions,
				 			'existedClasses'=>$this->getExistedClasses(),
				 			'grade'=>$grade	
				]
			]);

			$this->fields['name']['visible'] = false;
			$this->fields['students']['visible'] = false;
			$this->fields['staff_id']['visible'] = false;
			$this->fields['staff_id']['type'] = 'hidden';
			$this->setFieldOrder([
				'academic_period_id', 'education_grade', 'institution_shift_id', 'class_number', 'number_of_classes', 'single_grade_field'
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
			$this->field('multi_grade_field', [
				'type' => 'element', 
				'element' => 'Institution.Classes/multi_grade',
				'model' => $this->alias(),
				'field' => 'multi_grade_field',
				'data' => $gradeOptions
			]);
			$this->fields['staff_id']['options'] = $staffOptions;
			$this->fields['students']['visible'] = false;
			$this->setFieldOrder([
				'academic_period_id', 'name', 'institution_shift_id', 'staff_id', 'multi_grade_field'
			]);

    	}

		$this->Navigation->substituteCrumb(ucwords(strtolower($this->action)), ucwords(strtolower($this->action)).' '.ucwords(strtolower($this->_selectedGradeType)).' Grade');

		$tabElements = [
			'single' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Classes', $this->action, 'grade_type'=>'single'],
				'text' => __('Single Grade')
			],
			'multi' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Classes', $this->action, 'grade_type'=>'multi'],
				'text' => __('Multi Grade')
			],
		];
        $this->controller->set('tabElements', $tabElements);
	}

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		if ($this->_selectedGradeType == 'single') {
			$process = function ($model, $entity) use ($data) {
				$commonData = $data['InstitutionClasses'];
				/**
				 * PHPOE-2090, check if grade is empty as it is mandatory
				 */
				if (!empty($commonData['education_grade'])) {
					foreach($data['MultiClasses'] as $key => $row) {
						$data['MultiClasses'][$key]['institution_shift_id'] = $commonData['institution_shift_id'];
						$data['MultiClasses'][$key]['institution_id'] = $commonData['institution_id'];
						$data['MultiClasses'][$key]['academic_period_id'] = $commonData['academic_period_id'];
						$data['MultiClasses'][$key]['institution_class_grades'][0] = [
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
						$model->fields['single_grade_field']['data']['classes'] = $classes;
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
			 * using the entity->id (class id), find the list of grades from (institution_class_grades) linked to this class
			 */
			$grades = [];
			foreach ($entity->institution_class_grades as $grade) {
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
			 * using the list of primary keys, search institution_class_subjects (InstitutionClassSubjects) to check for existing records
			 * if found, don't insert, 
			 * else create a record in institution_classes (InstitutionSubjects)
			 * and link to the subject in institution_class_subjects (InstitutionClassSubjects) with status 1
			 */
			$InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
			$newSchoolSubjects = [];

			foreach ($educationSubjects as $key=>$educationSubject) {
				$getExistingRecord = false;
				if (empty($institutionSubjects)) {
					if (array_key_exists($key, $institutionSubjectsIds)) {
						$getExistingRecord = $InstitutionClassSubjects->find()
							->where([
								$InstitutionClassSubjects->aliasField('institution_class_id') => $entity->id,
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
						'institution_class_subjects' => [
							[
								'status' => 1,
								'institution_class_id' => $entity->id
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
			if (isset($data['InstitutionClasses']['institution_class_grades']) && count($data['InstitutionClasses']['institution_class_grades'])>0) {
				foreach($data['InstitutionClasses']['institution_class_grades'] as $key => $row) {
					$data['InstitutionClasses']['institution_class_grades'][$key]['status'] = 1;
				}
			} else {
				/**
				 * set institution_id to empty to trigger validation error in ControllerActionComponent
				 */
				$data['InstitutionClasses']['institution_id'] = '';
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
				'action' => 'Classes'
			]);
		}

		$this->setFieldOrder([
			'academic_period_id', 'name', 'institution_shift_id', 'staff_id', 'students',
		]);
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		/**
		 * System is unable to cope if there are too many students to be added.
		 * Temporarily extend the server's max_execution_time to 60 seconds.
		 * @todo  Changed the way to save huge hasMany records.
		 */
		ini_set('max_execution_time', 60);

		/**
		 * In students.ctp, we set the student_id as the array keys for easy search and compare.
		 * Assign back original record's id to the new list so as to preserve id numbers.
		 */
		foreach($entity->institution_class_students as $key => $record) {
			$k = $record->student_id;
			if (array_key_exists('institution_class_students', $data[$this->alias()])) {
				if (!array_key_exists($k, $data[$this->alias()]['institution_class_students'])) {			
					// PHPOE-2338 - status no longer used, record will be deleted instead
				} else {
					$data[$this->alias()]['institution_class_students'][$k]['id'] = $record->id;
				}
			} else {
				$data[$this->alias()]['institution_class_students'] = [];
				// PHPOE-2338 - status no longer used, record will be deleted instead
			}
		}
	}

	public function editAfterAction(Event $event, Entity $entity) {
		/**
		 * @todo  add this max limit to config
		 * This limit value is being used in ValidationBehavior->checkInstitutionClassMaxLimit() and ImportStudents as well
		 */
		$maxNumberOfStudents = 100;

		$students = $entity->institution_class_students;
		$studentOptions = $this->getStudentsOptions($entity);
		/**
		 * Check if the request is a page reload
		 */
		if (count($this->request->data)>0 && $this->request->data['submit']=='add') {
			// clear institution_class_students list grab from db
			$existingStudents = $students;
			$students = [];
			/**
			 * Populate records in the UI table & unset the record from studentOptions
			 */
			if (array_key_exists('institution_class_students', $this->request->data[$this->alias()])) {
				foreach ($this->request->data[$this->alias()]['institution_class_students'] as $row) {
					if (array_key_exists($row['student_id'], $studentOptions)) {
						$id = $row['student_id'];
						if ($id != 0) {
							$students[] = $this->createVirtualStudentEntity($id, $entity);
						}
						unset($studentOptions[$id]);
					}
				}
			}
			if (count($students)<$maxNumberOfStudents) {
				/**
				 * Insert the newly added record into the UI table & unset the record from studentOptions
				 */
				if (array_key_exists('student_id', $this->request->data)) {
					if ($this->request->data['student_id']>0) {
						$id = $this->request->data['student_id'];
						if ($id != 0) {
							$students[] = $this->createVirtualStudentEntity($id, $entity);
						}
						unset($studentOptions[$id]);
					} else if ($this->request->data['student_id'] == -1) {
						foreach ($studentOptions as $id => $name) {
							if (count($students)==$maxNumberOfStudents) {
								$this->Alert->warning($this->aliasField('maximumStudentsReached'));
								break;
							}
							if ($id > 0) {
								$students[] = $this->createVirtualStudentEntity($id, $entity);
								unset($studentOptions[$id]);
							}
						}
					}
				}
			} else {
				$this->Alert->warning($this->aliasField('maximumStudentsReached'));
			}
		} else {
			/**
			 * Just unset the record from studentOptions on first page load
			 */
			foreach ($entity->institution_class_students as $row) {
				if (array_key_exists($row->student_id, $studentOptions)) {
					unset($studentOptions[$row->student_id]);
				}
			}
		}
		if (count($studentOptions) < 3) {
			$studentOptions = [$this->getMessage('Users.select_student_empty')];
		}
		$this->fields['students']['data']['students'] = $students;
		$this->fields['students']['data']['studentOptions'] = $studentOptions;
	}

	public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions) {

		$currentInstitutionClassStudents = $entity->institution_class_students;
		$currentStudentIds = [];
		foreach ($currentInstitutionClassStudents as $key => $value) {
			$currentStudentIds[] = $value->student_id;
		}

		$originalInstitutionClassStudents = $entity->getOriginal('institution_class_students');
		$originalStudentIds = [];
		foreach ($originalInstitutionClassStudents as $key => $value) {
			$originalStudentIds[] = $value->student_id;
		}
		$removedStudentIds = array_diff($originalStudentIds, $currentStudentIds);

		if (!empty($removedStudentIds)) {
			// 'deleteAll will not trigger beforeDelete/afterDelete events. If you need those first load a collection of records and delete them.'
			foreach ($removedStudentIds as $key => $value) {
				$deleteClassStudent = $this->InstitutionClassStudents->find()
					->where([
						$this->InstitutionClassStudents->aliasField('institution_class_id') => $entity->id,
						$this->InstitutionClassStudents->aliasField('student_id').' IN ' => $removedStudentIds
					])
					->toArray()
					;
				foreach ($deleteClassStudent as $key => $value) {
					$this->InstitutionClassStudents->delete($value);
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
		$academicPeriodOptions = $this->AcademicPeriods->getlist(['isEditable'=>true]);
		if ($action == 'edit') {
		
			$attr['type'] = 'readonly';
			if ($this->_selectedAcademicPeriodId > -1) {
				$attr['attr']['value'] = $this->AcademicPeriods->get($this->_selectedAcademicPeriodId)->name;
			}

		} elseif ($action == 'add') {

			$attr['options'] = $academicPeriodOptions;
			$attr['onChangeReload'] = true;
			$attr['default'] = $this->AcademicPeriods->getCurrent();
		
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

	public function onGetStaffId(Event $event, Entity $entity) {
		if ($this->action == 'view') {
			return $event->subject()->Html->link($entity->staff->name_with_id , [
				'plugin' => 'Institution',
				'controller' => 'Institutions',
				'action' => 'StaffUser',
				'view',
				$entity->staff->id
			]);
		} else {
			if ($entity->has('staff')) {
				return $entity->staff->name_with_id;
			} else {
				return __('No Teacher Assigned');
			}
			
		}		
	}


/******************************************************************************************************************
**
** essential functions
**
******************************************************************************************************************/

	protected function getClassGradeOptions($entity) {
		$Grade = $this->InstitutionClassGrades;
		$gradeOptions = $Grade->find()
							->contain('EducationGrades')
							->where([
								$Grade->aliasField('institution_class_id') => $entity->id,
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
	 * @param  [type] $classEntity [description]
	 * @return [type]                [description]
	 */
	protected function getStudentsOptions($classEntity) {
		
		$academicPeriodObj = $this->AcademicPeriods->get($this->_selectedAcademicPeriodId);
		$classGradeObjects = $classEntity->institution_class_grades;
		$classGrades = [];
		foreach ($classGradeObjects as $key=>$value) {
			$classGrades[] = $value->education_grade_id;
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
		if (!empty($query)) {
			$studentOptions[-1] = $this->getMessage('Users.add_all_student');
		}
		foreach ($query as $skey => $obj) {
			/**
			 * Modified this filter in PHPOE-1799.
			 * Use institution_students table through $this->Institutions->Students where Students being the table alias.
			 */
			if (in_array($obj->education_grade_id, $classGrades)) {
				if (isset($obj->user)) {
					$studentOptions[$obj->user->id] = $obj->user->name_with_id;
				} else {
					$this->log('Data corrupted with no security user for student: '. $obj->id, 'debug');
				}
			}
		}
		$studentOptions = $this->attachClassInfo($classEntity->id, $studentOptions);
		return $studentOptions;
	}

	public function attachClassInfo($id, $studentOptions) {
		$query = $this->InstitutionClassStudents->find()
					->contain(['InstitutionClasses'])
					->where([
						$this->aliasField('institution_id') => $this->institutionId,
						$this->aliasField('academic_period_id') => $this->_selectedAcademicPeriodId,
					])
					->where([
							$this->InstitutionClassStudents->aliasField('student_id').' IN' => array_keys($studentOptions)
						]);
		$classesWithStudents = $query->toArray();

		foreach($classesWithStudents as $student) {
			if($student->institution_class_id != $id) {
				if (!isset($studentOptions[$student->institution_class->name])) {
					$studentOptions[$student->institution_class->name] = ['text' => 'Class '.$student->institution_class->name, 'options' => [], 'disabled' => true];
				}
				$studentOptions[$student->institution_class->name]['options'][] = ['value' => $student->student_id, 'text' => $studentOptions[$student->student_id]];
				unset($studentOptions[$student->student_id]);
			}
		}
		return $studentOptions;
	}

	protected function getStaffOptions($action='edit') {
		if (in_array($action, ['edit', 'add'])) {
			$options = [0=>'-- ' . __('Select Teacher or Leave Blank') . ' --'];
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
					$options[$value->user->id] = $value->user->name_with_id;
				}
			}
		}

		return $options;
	}

	private function numberOfClassesOptions() {
		$total = 10;
		$options = [];
		for($i=1; $i<=$total; $i++){
			$options[$i] = $i;
		}
		
		return $options;
	}
	
	private function getExistedClasses() {
		$classesByGrade = $this->InstitutionClassGrades
			->find('list', [
				'keyField'=>'id',
				'valueField'=>'institution_class_id'
			])
			->where([$this->InstitutionClassGrades->aliasField('education_grade_id') => $this->_selectedEducationGradeId])
			->toArray();

		$data = $this->find('list', [
				'keyField' => 'id',
			    'valueField' => 'name'
			])
			->where([
				/**
				 * If class_number is null, it is considered as a multi-grade class
				 */
				$this->aliasField('class_number').' IS NOT NULL',
				$this->aliasField('institution_id') => $this->institutionId,
				$this->aliasField('academic_period_id') => $this->_selectedAcademicPeriodId,
				$this->aliasField('id').' IN' => $classesByGrade,
			])
			->toArray()
			;
		return $data;
	}

	protected function createVirtualStudentEntity($id, $entity) {
		$InstitutionStudentsTable = $this->Institutions->Students;
		$userData = $InstitutionStudentsTable->find()
			->contain(['Users'=>['Genders'], 'StudentStatuses', 'EducationGrades'])
			->where([
				$InstitutionStudentsTable->aliasField('student_id') => $id,
				$InstitutionStudentsTable->aliasField('academic_period_id') => $entity->academic_period_id,
				$InstitutionStudentsTable->aliasField('institution_id') => $entity->institution_id
			])
			->first();

		$data = [
			'id'=>$this->getExistingRecordId($id, $entity),
			'student_id'=>$id,
			'institution_class_id'=>$entity->id,
			'education_grade_id'=>  $userData->education_grade_id,
			'student_status_id' => $userData->student_status_id,
			'education_grade' => [],
			'student_status' => [],
			'user'=>[]
		];
		$student = $this->InstitutionClassStudents->newEntity();
		$student = $this->InstitutionClassStudents->patchEntity($student, $data);
		$student->user = $userData->user;
		$student->student_status = $userData->student_status;
		$student->education_grade = $userData->education_grade;
		return $student;
	}

	protected function getExistingRecordId($securityId, $entity) {
		$id = Text::uuid();
		foreach ($entity->institution_class_students as $student) {
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
					$this->_selectedAcademicPeriodId = $this->AcademicPeriods->getCurrent();
				}
			} else {
				$this->_selectedAcademicPeriodId = $this->AcademicPeriods->getCurrent();
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
	public function getClassOptions($academicPeriodId, $institutionId, $gradeId=false) {
		$multiGradeOptions = array(
			'fields' => array('InstitutionClasses.id', 'InstitutionClasses.name'),
			'conditions' => array(
				'InstitutionClasses.academic_period_id' => $academicPeriodId,
				'InstitutionClasses.institution_id' => $institutionId
			),
			'order' => array('InstitutionClasses.name')
		);

		if($gradeId != false) {
			$multiGradeOptions['join'] = array(
				array(
					'table' => 'institution_class_grades',
					'alias' => 'InstitutionClassGrades',
					'conditions' => array(
						'InstitutionClassGrades.institution_class_id = InstitutionClasses.id',
						'InstitutionClassGrades.education_grade_id = ' . $gradeId,
						'InstitutionClassGrades.status = 1'
					)
				)
			);
			$multiGradeOptions['group'] = array('InstitutionClasses.id');
		}

		$multiGradeData = $this->find('list', $multiGradeOptions);
		return $multiGradeData->toArray();
	}
}
	