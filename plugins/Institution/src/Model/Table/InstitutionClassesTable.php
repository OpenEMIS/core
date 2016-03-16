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

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', 		['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Staff', 					['className' => 'User.Users', 						'foreignKey' => 'staff_id']);
		$this->belongsTo('InstitutionShifts',		['className' => 'Institution.InstitutionShifts', 	'foreignKey' => 'institution_shift_id']);
		$this->belongsTo('Institutions', 			['className' => 'Institution.Institutions', 		'foreignKey' => 'institution_id']);

		$this->hasMany('ClassGrades', 				['className' => 'Institution.InstitutionClassGrades', 'dependent' => true]);
		$this->hasMany('ClassStudents', 			['className' => 'Institution.InstitutionClassStudents', 'dependent' => true]);
		$this->hasMany('SubjectStudents', 			['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true]);

		$this->belongsToMany('EducationGrades', [
			'className' => 'Education.EducationGrades',
			'through' => 'Institution.InstitutionClassGrades',
			'foreignKey' => 'institution_class_id',
			'targetForeignKey' => 'education_grade_id',
		]);

		$this->belongsToMany('Students', [
			'className' => 'User.Users',
			'through' => 'Institution.InstitutionClassStudents',
			'foreignKey' => 'institution_class_id',
			'targetForeignKey' => 'student_id',
		]);

		$this->belongsToMany('InstitutionSubjects', [
			'className' => 'Institution.InstitutionSubjects',
			'through' => 'Institution.InstitutionClassSubjects',
			'foreignKey' => 'institution_class_id',
			'targetForeignKey' => 'institution_subject_id'
		]);

		/**
		 * Shortcuts
		 */
		$this->InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');

		// this behavior restricts current user to see All Classes or My Classes
		$this->addBehavior('Security.InstitutionClass');
		$this->addBehavior('AcademicPeriod.AcademicPeriod');
	}

	public function validationDefault(Validator $validator) {
		$validator
			->requirePresence('name')
			->add('name', 'ruleUniqueNamePerAcademicPeriod', [
	        		'rule' => 'uniqueNamePerAcademicPeriod',
	        		'provider' => 'table',
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

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.delete.afterAction'] = ['callable' => 'deleteAfterAction', 'priority' => 10];
		return $events;
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$query = $this->request->query;

		$institutionId = $this->Session->read('Institution.Institutions.id');
		$extra['institution_id'] = $institutionId;
		$academicPeriodOptions = $this->getAcademicPeriodOptions($institutionId);
		$selectedAcademicPeriodId = $this->AcademicPeriods->getCurrent();

		if ($this->action == 'index') {
			if (empty($query['academic_period_id'])) {
				$query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
			}
		} else if ($this->action == 'add') {
			$selectedGradeType = 'single';
	    	if (array_key_exists('grade_type', $query)) {
	    		$selectedGradeType = $query['grade_type'];
	    	}
			$gradeBehaviors = ['Institution.SingleGrade', 'Institution.MultiGrade'];
			foreach ($gradeBehaviors as $key => $behavior) {
				if ($this->hasBehavior($behavior)) {
					$this->removeBehavior($behavior);
				}
			}
			if ($selectedGradeType == 'single') {
				$this->addBehavior('Institution.SingleGrade');
	    	} else {
				$this->addBehavior('Institution.MultiGrade');
	    	}
	    	$extra['selectedGradeType'] = $selectedGradeType;
		}
		if (array_key_exists($this->alias(), $this->request->data)) {
			$selectedAcademicPeriodId = $this->postString('academic_period_id', $academicPeriodOptions);
		} else if ($this->action == 'edit' && isset($this->request->pass[1])) {
			$id = $this->request->pass[1];
			if ($this->exists($id)) {
				$selectedAcademicPeriodId = $this->get($id)->academic_period_id;
			}
		}

		$extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;

		$this->field('class_number', ['visible' => false]);
		$this->field('modified_user_id', ['visible' => false]);
		$this->field('modified', ['visible' => false]);
		$this->field('created_user_id', ['visible' => false]);
		$this->field('created', ['visible' => false]);

		$this->field('academic_period_id', ['type' => 'select', 'visible' => ['view'=>true, 'edit'=>true]]);
		$this->field('institution_shift_id', ['type' => 'select', 'visible' => ['view'=>true, 'edit'=>true]]);

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
		]);
		$this->field('education_grades', [
			'type' => 'element',
			'element' => 'Institution.Classes/multi_grade',
			'data' => [	
				'grades'=>[]
			],
			'visible' => ['view'=>true]
		]);

		$this->field('staff_id', ['type' => 'select', 'options' => [], 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);

		$this->setFieldOrder([
			'name', 'staff_id', 'male_students', 'female_students', 'subjects',
		]);

	}

	public function afterAction(Event $event, ArrayObject $extra) {
		$action = $this->action;
		if ($action != 'add') {
			$staffOptions = [];
			$selectedAcademicPeriodId = $extra['selectedAcademicPeriodId'];
			$institutionId = $extra['institution_id'];
			if ($selectedAcademicPeriodId > -1) {
				if ($action == 'index') {
					$action = 'view';
				}
				$staffOptions = $this->getStaffOptions($action, $selectedAcademicPeriodId, $institutionId);
			}
			$this->fields['staff_id']['options'] = $staffOptions;
		}
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		if ($entity->isNew()) {
			// $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
			$this->InstitutionSubjects->autoInsertSubjectsByClass($entity);
		}
	}


/******************************************************************************************************************
**
** delete action methods
**
******************************************************************************************************************/
	public function deleteAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$errorMessage = $this->aliasField('stopDeleteWhenStudentExists');
		if (isset($extra['errorMessage']) && $extra['errorMessage']==$errorMessage) {
			$this->Alert->warning($errorMessage, ['reset'=>true]);
		}
	}

	public function onBeforeDelete(Event $event, Entity $entity, ArrayObject $extra) {
		$Students = $this->ClassStudents;
		$conditions = [$Students->aliasField($Students->foreignKey()) => $entity->id];
		if ($Students->exists($conditions)) {
			$extra['errorMessage'] = $this->aliasField('stopDeleteWhenStudentExists');
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
			$action = $this->url('index');
			unset($action['grade_type']);
			$this->controller->redirect($action);
    	}

		$Classes = $this;
		$academicPeriodOptions = $this->AcademicPeriods->getList();

		$institutionId = $extra['institution_id'];
		$selectedAcademicPeriodId = $this->queryString('academic_period_id', $academicPeriodOptions);
		$this->advancedSelectOptions($academicPeriodOptions, $selectedAcademicPeriodId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
			'callable' => function($id) use ($Classes, $institutionId) {
				return $Classes->find()
					->where([
						$Classes->aliasField('institution_id') => $institutionId,
						$Classes->aliasField('academic_period_id') => $id
					])
					->count();
			}
		]);
		$extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;
		$gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptionsForIndex($institutionId, $selectedAcademicPeriodId);
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
		
		$selectedEducationGradeId = $this->queryString('education_grade_id', $gradeOptions);
		$this->advancedSelectOptions($gradeOptions, $selectedEducationGradeId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
			'callable' => function($id) use ($Classes, $institutionId, $selectedAcademicPeriodId) {
				/**
				 * If statement added on PHPOE-1762 for PHPOE-1766
				 * If $id is -1, get all classes under the selected academic period
				 */
				
				$join = [
					'table' => 'institution_class_grades',
					'alias' => 'InstitutionClassGrades',
					'conditions' => [
						'InstitutionClassGrades.institution_class_id = InstitutionClasses.id'
					]
				];

				if ($id > 0) {
					$join['conditions']['InstitutionClassGrades.education_grade_id'] = $id;
				}

				$query = $Classes->find()
						->join([$join])
						->where([
							$Classes->aliasField('institution_id') => $institutionId,
							$Classes->aliasField('academic_period_id') => $selectedAcademicPeriodId,
						]);
				return $query->count();
			}
		]);
		$extra['selectedEducationGradeId'] = $selectedEducationGradeId;

		$extra['elements']['control'] = [
			'name' => 'Institution.Classes/controls', 
			'data' => [
            	'academicPeriodOptions'=>$academicPeriodOptions, 
            	'selectedAcademicPeriod'=>$selectedAcademicPeriodId, 
            	'gradeOptions'=>$gradeOptions, 
            	'selectedGrade'=>$selectedEducationGradeId, 
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
		->find('byGrades', ['education_grade_id' => $extra['selectedEducationGradeId']])
		->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']])
		/**
		 * Added on PHPOE-1762 (extra feature)
		 */
		->order([$this->aliasField('name')=>$direction])
		/**/
		;
	}

    public function findByGrades(Query $query, array $options) {
    	$gradeId = $options['education_grade_id'];
    	$join = [
			'table' => 'institution_class_grades',
			'alias' => 'InstitutionClassGrades',
			'conditions' => [
				'InstitutionClassGrades.institution_class_id = InstitutionClasses.id'
			]
		];

    	if ($gradeId > 0) {
    		$join['conditions']['InstitutionClassGrades.education_grade_id'] = $gradeId;
		}
		return $query->join([$join])->group(['InstitutionClassGrades.institution_class_id']);
    }


/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
    public function viewBeforeAction(Event $event, ArrayObject $extra) {
		if ($extra['selectedAcademicPeriodId'] == -1) {
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
			$this->controller->redirect($action);
    	}

		$this->setFieldOrder([
			'academic_period_id', 'name', 'institution_shift_id', 'education_grades', 'staff_id', 'students'
		]);

	}

	public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$query->contain([
			'AcademicPeriods',
			'InstitutionShifts',
			'EducationGrades',
			'Staff',
			'ClassStudents' => [
				'Users.Genders',
				'EducationGrades',
				'StudentStatuses'
			],
		]);
	}

	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$this->fields['students']['data']['students'] = $entity->class_students;
		$this->fields['education_grades']['data']['grades'] = $entity->education_grades;

		$academicPeriodOptions = $this->getAcademicPeriodOptions($entity->institution_id);
	}


/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
	// selected grade_type behavior's addBeforeAction will be called later
    public function addBeforeAction(Event $event, ArrayObject $extra) {
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
    	$selectedAcademicPeriodId = $extra['selectedAcademicPeriodId'];
		if (array_key_exists($this->alias(), $this->request->data)) {
			$academicPeriodOptions = $this->getAcademicPeriodOptions($extra['institution_id']);
			$selectedAcademicPeriodId = $this->postString('academic_period_id', $academicPeriodOptions);
		}
		if ($selectedAcademicPeriodId == -1) {
			return $this->controller->redirect([
				'plugin' => $this->controller->plugin, 
				'controller' => $this->controller->name, 
				'action' => 'Classes'
			]);
		}
		$extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;
		$extra['selectedEducationGradeId'] = 0;

		$this->Navigation->substituteCrumb(ucwords(strtolower($this->action)), ucwords(strtolower($this->action)).' '.ucwords(strtolower($extra['selectedGradeType'])).' Grade');

		$tabElements = [
			'single' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Classes', 'add', 'grade_type'=>'single'],
				'text' => $this->getMessage($this->aliasField('singleGrade'))
			],
			'multi' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Classes', 'add', 'grade_type'=>'multi'],
				'text' => $this->getMessage($this->aliasField('multiGrade'))
			],
		];
        $this->controller->set('tabElements', $tabElements);
	}

	public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$academicPeriodOptions = $this->AcademicPeriods->getlist(['isEditable'=>true]);
		$this->fields['academic_period_id']['options'] = $academicPeriodOptions;
		$this->fields['academic_period_id']['onChangeReload'] = true;
		$this->fields['academic_period_id']['default'] = $this->AcademicPeriods->getCurrent();

		$this->controller->set('selectedAction', $extra['selectedGradeType']);
	}


/******************************************************************************************************************
**
** edit action methods
**
******************************************************************************************************************/
	public function editBeforeAction(Event $event, ArrayObject $extra) {
		if ($extra['selectedAcademicPeriodId'] == -1) {
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

	public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$query->contain([
			'InstitutionSubjects',
			'SubjectStudents',
		]);
	}

	/**
	 * editBeforePatch is a ControllerAction events to implements additional logics before the request data is patch to an entity
	 *
	 * In InstitutionClasses editBeforePatch, subject_students array is being built using class_students and institution_classes data.
	 * The built subject_students array will be added to request data so that students will be added to subjects through associative save.
	 * 
	 * @param  Event       $event        Event object
	 * @param  Entity      $entity       Entity object
	 * @param  ArrayObject $requestData  HTTP request data as an ArrayObject
	 * @param  ArrayObject $patchOptions Options for patching entity as an ArrayObject
	 * @param  ArrayObject $extra        Extra parameters to be passed to other ControllerAction events as an ArrayObject
	 */
	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra) {
		$data = $requestData[$this->alias()];
		$subjectStudents = [];
		if (array_key_exists('class_students', $data)) {
			foreach ($entity->institution_subjects as $key => $subjectEntity) {
				foreach ($data['class_students'] as $classStudent) {
					$subjectStudents[] = [
						'status' => 1,
                        'student_id' => $classStudent['student_id'],
						'institution_subject_id' => $subjectEntity->id,
                        'institution_class_id' => $classStudent['institution_class_id'],
                	];
				}
			}
		} else {
			$data['class_students'] = [];
		}
		$data['subject_students'] = $subjectStudents;

		$requestData[$this->alias()] = $data;
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		/**
		 * @todo  add this max limit to config
		 * This limit value is being used in ValidationBehavior->checkInstitutionClassMaxLimit() and ImportStudents as well
		 */
		$maxNumberOfStudents = 100;

		$students = $entity->class_students;
		$studentOptions = $this->getStudentsOptions($entity);
		/**
		 * Check if the request is a page reload
		 */
		if (count($this->request->data)>0 && $this->request->data['submit']=='add') {
			// clear class_students list grab from db
			$existingStudents = $students;
			$students = [];
			/**
			 * Populate records in the UI table & unset the record from studentOptions
			 */
			if (array_key_exists('class_students', $this->request->data[$this->alias()])) {
				foreach ($this->request->data[$this->alias()]['class_students'] as $row) {
					if (array_key_exists($row['student_id'], $studentOptions)) {
						$id = $row['student_id'];
						if ($id != 0) {
							$virtualStudent = $this->createVirtualStudentEntity($id, $entity);
							if ($virtualStudent) {
								$students[] = $virtualStudent;
							}
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
							$virtualStudent = $this->createVirtualStudentEntity($id, $entity);
							if ($virtualStudent) {
								$students[] = $virtualStudent;
							}
						}
						unset($studentOptions[$id]);
					} else if ($this->request->data['student_id'] == -1) {
						foreach ($studentOptions as $id => $name) {
							if (count($students)==$maxNumberOfStudents) {
								$this->Alert->warning($this->aliasField('maximumStudentsReached'));
								break;
							}
							if ($id > 0) {
								$virtualStudent = $this->createVirtualStudentEntity($id, $entity);
								if ($virtualStudent) {
									$students[] = $virtualStudent;
								}
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
			foreach ($students as $key => $student) {
				if (array_key_exists($student->student_id, $studentOptions)) {
					unset($studentOptions[$student->student_id]);
				}
				// POCOR-1694 - when there are new students added but the form submit fails validation,
				// the new students entity will not have security_users data and will produce notices.
				// Attach user data if it does not exists in the student entity
				if (!$student->has('user')) {
					$virtualStudent = $this->createVirtualStudentEntity($student->student_id, $entity);
					if ($virtualStudent) {
						$students[$key] = $virtualStudent;
					} else {
						unset($students[$key]);
					}
				}
			}
		}
		if (count($studentOptions) < 3) {
			$studentOptions = [$this->getMessage('Users.select_student_empty')];
		}
		$this->fields['students']['data']['students'] = $students;
		$this->fields['students']['data']['studentOptions'] = $studentOptions;

		$this->fields['academic_period_id']['type'] = 'readonly';
		if ($extra['selectedAcademicPeriodId'] > -1) {
			$this->fields['academic_period_id']['attr']['value'] = $this->AcademicPeriods->get($extra['selectedAcademicPeriodId'])->name;
		}
	}

	public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra) {
		$currentStudentIds = (new Collection($entity->class_students))->extract('student_id')->toArray();
		$originalStudentIds = (new Collection($entity->getOriginal('class_students')))->extract('student_id')->toArray();
		$removedStudentIds = array_diff($originalStudentIds, $currentStudentIds);
		
		if (!empty($removedStudentIds)) {
			// 'deleteAll will not trigger beforeDelete/afterDelete events. If you need those first load a collection of records and delete them.'
			$classStudentsToBeDeleted = $this->ClassStudents->find()
				->where([
					$this->ClassStudents->aliasField('institution_class_id') => $entity->id,
					$this->ClassStudents->aliasField('student_id').' IN ' => $removedStudentIds
				])
				->toArray()
				;
			foreach ($classStudentsToBeDeleted as $key => $value) {
				$this->ClassStudents->delete($value);
			}
		}
	}


/******************************************************************************************************************
**
** addEdit action methods
**
******************************************************************************************************************/
	public function addEditAfterAction (Event $event, Entity $entity, ArrayObject $extra) {
		$institutionId = $extra['institution_id'];
		$selectedAcademicPeriodId = $extra['selectedAcademicPeriodId'];

		if ($selectedAcademicPeriodId > -1) {
			$this->InstitutionShifts->createInstitutionDefaultShift($institutionId, $selectedAcademicPeriodId);
			$shiftOptions = $this->InstitutionShifts->getShiftOptions($institutionId, $selectedAcademicPeriodId);
		} else {
			$shiftOptions = [];
		}

		$this->fields['institution_shift_id']['options'] = $shiftOptions;
	}


/******************************************************************************************************************
**
** field specific methods
**
******************************************************************************************************************/
	public function onGetStaffId(Event $event, Entity $entity) {
		if ($this->action == 'view') {
			if ($entity->has('staff')) {
				return $event->subject()->Html->link($entity->staff->name_with_id , [
					'plugin' => 'Institution',
					'controller' => 'Institutions',
					'action' => 'StaffUser',
					'view',
					$entity->staff->id
				]);
			} else {
				return $this->getMessage($this->aliasField('noTeacherAssigned'));
			}
		} else {
			if ($entity->has('staff')) {
				return $entity->staff->name_with_id;
			} else {
				return $this->getMessage($this->aliasField('noTeacherAssigned'));
			}			
		}		
	}


/******************************************************************************************************************
**
** essential functions
**
******************************************************************************************************************/
	public function getClassGradeOptions($entity) {
		$Grade = $this->ClassGrades;
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
	private function getStudentsOptions($classEntity) {
		$academicPeriodId = $classEntity->academic_period_id;
		$academicPeriodObj = $this->AcademicPeriods->get($academicPeriodId);
		$classGradeObjects = $classEntity->education_grades;
		$classGrades = [];
		foreach ($classGradeObjects as $key=>$value) {
			$classGrades[] = $value->id;
		}

		/**
		 * Modified this query in PHPOE-1780. Use PeriodBehavior which is loaded InstitutionStudents, by adding ->find('AcademicPeriod', ['academic_period_id'=> $academicPeriodId])
		 * This is inline with how InstitutionClassesTable populate getStudentOptions.
		 */
		$students = $this->Institutions->Students;

		$query = $students
			->find('all')
			->find('AcademicPeriod', ['academic_period_id' => $academicPeriodId])
			->contain(['Users'])
			->where([
				$students->aliasField('institution_id') => $classEntity->institution_id
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
		$studentOptions = $this->attachClassInfo($classEntity, $studentOptions);
		return $studentOptions;
	}

	private function attachClassInfo($classEntity, $studentOptions) {
		$query = $this->ClassStudents->find()
					->contain(['InstitutionClasses'])
					->where([
						$this->aliasField('institution_id') => $classEntity->institution_id,
						$this->aliasField('academic_period_id') => $classEntity->academic_period_id,
					])
					->where([
							$this->ClassStudents->aliasField('student_id').' IN' => array_keys($studentOptions)
						]);
		$classesWithStudents = $query->toArray();

		foreach ($classesWithStudents as $student) {
			if ($student->institution_class_id != $classEntity->id) {
				if (!isset($studentOptions[$student->institution_class->name])) {
					$studentOptions[$student->institution_class->name] = ['text' => 'Class '.$student->institution_class->name, 'options' => [], 'disabled' => true];
				}
				$studentOptions[$student->institution_class->name]['options'][] = ['value' => $student->student_id, 'text' => $studentOptions[$student->student_id]];
				unset($studentOptions[$student->student_id]);
			}
		}
		return $studentOptions;
	}

	public function getStaffOptions($action='edit', $academicPeriodId=0, $institutionId) {
		if (in_array($action, ['edit', 'add'])) {
			$options = [0 => '-- ' . $this->getMessage($this->aliasField('selectTeacherOrLeaveBlank')) . ' --'];
		} else {
			$options = [0 => $this->getMessage($this->aliasField('noTeacherAssigned'))];
		}

		if (!empty($academicPeriodId)) {

			$academicPeriodObj = $this->AcademicPeriods->get($academicPeriodId);
			$startDate = $this->AcademicPeriods->getDate($academicPeriodObj->start_date);
	        $endDate = $this->AcademicPeriods->getDate($academicPeriodObj->end_date);

	        $Staff = $this->Institutions->Staff;
			$query = $Staff->find('all')
							->find('withBelongsTo')
							->find('byPositions', ['Institutions.id' => $institutionId, 'type' => 1]) // refer to OptionsTrait for type options
							->find('byInstitution', ['Institutions.id'=>$institutionId])
							->find('AcademicPeriod', ['academic_period_id'=>$academicPeriodId])
							;

			foreach ($query->toArray() as $key => $value) {
				if ($value->has('user')) {
					$options[$value->user->id] = $value->user->name_with_id;
				}
			}
		}

		return $options;
	}
	
	public function getExistedClasses($institutionId, $academicPeriodId, $educationGradeId) {
		$data = $this->find('list', [
				'keyField' => 'id',
			    'valueField' => 'name'
			])
			->join([
				[
					'table' => 'institution_class_grades',
					'alias' => 'InstitutionClassGrades',
					'conditions' => [
						'InstitutionClassGrades.institution_class_id = ' . $this->aliasField('id'),
						'InstitutionClassGrades.education_grade_id = ' . $educationGradeId
					]
				]
			])
			->where([
				/**
				 * If class_number is null, it is considered as a multi-grade class
				 */
				$this->aliasField('class_number').' IS NOT NULL',
				$this->aliasField('institution_id') => $institutionId,
				$this->aliasField('academic_period_id') => $academicPeriodId
			])
			->toArray()
			;
		return $data;
	}

	public function createVirtualStudentEntity($id, $entity) {
		$InstitutionStudentsTable = $this->Institutions->Students;
		$userData = $InstitutionStudentsTable->find()
			->contain(['Users' => ['Genders'], 'StudentStatuses', 'EducationGrades'])
			->where([
				$InstitutionStudentsTable->aliasField('student_id') => $id,
				$InstitutionStudentsTable->aliasField('academic_period_id') => $entity->academic_period_id,
				$InstitutionStudentsTable->aliasField('institution_id') => $entity->institution_id
			])
			->first();

		$data = [
			'id' => $this->getExistingRecordId($id, $entity),
			'student_id' => $id,
			'institution_class_id' => $entity->id,
			'education_grade_id'=>  $userData->education_grade_id,
			'student_status_id' => $userData->student_status_id,
			'education_grade' => [],
			'student_status' => [],
			'user' => []
		];
		$student = $this->ClassStudents->newEntity();
		$student = $this->ClassStudents->patchEntity($student, $data);
		$student->user = $userData->user;
		$student->student_status = $userData->student_status;
		$student->education_grade = $userData->education_grade;
		return $student;
	}

	public function getExistingRecordId($securityId, $entity) {
		$id = Text::uuid();
		foreach ($entity->class_students as $student) {
			if ($student->student_id == $securityId) {
				$id = $student->id;
			}
		}
		return $id;
	}

	private function getAcademicPeriodOptions($institutionId) {
		$InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
		$conditions = [$InstitutionGrades->aliasField('institution_id') => $institutionId];
		return $InstitutionGrades->getAcademicPeriodOptions($this->Alert, $conditions);
	}
	
	/**
	 * Used by Institution/UserBehavior && Institution/InstitutionStudentsTable
	 * @param  [integer]  $academicPeriodId [description]
	 * @param  [integer]  $institutionId    [description]
	 * @param  boolean $gradeId          [description]
	 * @return [type]                    [description]
	 */
	public function getClassOptions($academicPeriodId, $institutionId, $gradeId=false) {
		$multiGradeOptions = [
			'fields' => ['InstitutionClasses.id', 'InstitutionClasses.name'],
			'conditions' => [
				'InstitutionClasses.academic_period_id' => $academicPeriodId,
				'InstitutionClasses.institution_id' => $institutionId
			],
			'order' => ['InstitutionClasses.name']
		];

		if($gradeId != false) {
			$multiGradeOptions['join'] = [
				[
					'table' => 'institution_class_grades',
					'alias' => 'InstitutionClassGrades',
					'conditions' => [
						'InstitutionClassGrades.institution_class_id = InstitutionClasses.id',
						'InstitutionClassGrades.education_grade_id = ' . $gradeId
					]
				]
			];
			$multiGradeOptions['group'] = ['InstitutionClasses.id'];
		}

		$multiGradeData = $this->find('list', $multiGradeOptions);
		return $multiGradeData->toArray();
	}

	
}
