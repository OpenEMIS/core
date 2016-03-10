<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Collection\Collection;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

class InstitutionSubjectsTable extends ControllerActionTable {
	use MessagesTrait;

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', 			['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', 				['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('EducationSubjects', 			['className' => 'Education.EducationSubjects']);
		
		$this->hasMany('InstitutionClassSubjects', ['className' => 'Institution.InstitutionClassSubjects']);
		$this->hasMany('InstitutionSubjectStudents', 	['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true]);
		$this->hasMany('InstitutionSubjectStaff', 	['className' => 'Institution.InstitutionSubjectStaff']);

		$this->belongsToMany('InstitutionClasses', [
			'className' => 'Institution.InstitutionClasses',
			'joinTable' => 'institution_class_subjects',
			'foreignKey' => 'institution_subject_id',
			'targetForeignKey' => 'institution_class_id'
		]);

		$this->belongsToMany('Teachers', [
			'className' => 'User.Users',
			'through' => 'InstitutionSubjectStaff',
			'conditions' => ['InstitutionSubjectStaff.status' => 1],
			'foreignKey' => 'institution_subject_id',
			'targetForeignKey' => 'staff_id'
		]);

		$this->belongsToMany('Students', [
			'className' => 'User.Users',
			'through' => 'InstitutionSubjectStudents',
			'foreignKey' => 'institution_subject_id',
			'targetForeignKey' => 'student_id',
			'dependent' => true
		]);

		// this behavior restricts current user to see All Subjects or My Subjects
		$this->addBehavior('Security.InstitutionSubject');
		
		// $this->belongsToMany('InstitutionClasses', ['through' => 'InstitutionClassSubjects']);

		/**
		 * Short cuts 
		 */
		$this->InstitutionStudents = TableRegistry::get('Institution.InstitutionStudents');
		$this->InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
		$this->InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
		$this->addBehavior('AcademicPeriod.AcademicPeriod');
	}

	public function validationDefault(Validator $validator) {
		$validator->requirePresence('name');
		return $validator;
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$extra['institution_id'] = $this->Session->read('Institution.Institutions.id');
    	$this->field('academic_period_id', ['type' => 'select', 'visible' => ['view'=>true, 'edit'=>true, 'add'=>true], 'onChangeReload' => true]);
    	$this->field('created', ['type' => 'string', 'visible' => false]);
    	$this->field('created_user_id', ['type' => 'string', 'visible' => false]);
		$this->field('education_subject_code', ['type' => 'string', 'visible' => ['view'=>true]]);
		$this->field('education_subject_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
    	$this->field('modified', ['type' => 'string', 'visible' => false]);
    	$this->field('modified_user_id', ['type' => 'string', 'visible' => false]);
    	$this->field('name', ['type' => 'string', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
		$this->field('no_of_seats', ['type' => 'integer', 'attr'=>['min' => 1], 'visible' => false]);
		$this->field('class_name', ['type' => 'select', 'visible' => ['view'=>true], 'onChangeReload' => true]);

		$this->field('students', [
			'label' => '',
			'override' => true,
			'type' => 'element',
			'element' => 'Institution.Subjects/students',
			'data' => [	
				'students'=>[],
				'studentOptions'=>[],
				'categoryOptions'=>[]
			],
			'visible' => ['view'=>true, 'edit'=>true]
		]);
		$this->field('subjects', [
			'label' => '',
			'type' => 'element',
			'element' => 'Institution.Subjects/subjects',
			'data' => [	
				'subjects'=>[],
				'teachers'=>[]
			],
			'visible' => false
		]);

		$this->field('teachers', [
			'type' => 'chosenSelect',
			'fieldNameKey' => 'teachers',
			'fieldName' => $this->alias() . '.teachers._ids',
			'placeholder' => $this->getMessage('Users.select_teacher'),
			'valueWhenEmpty' => __('No Teacher Assigned'),
			'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]
		]);

		$this->field('male_students', [
			'type' => 'integer',
			'visible' => ['index'=>true]
		]);
		$this->field('female_students', [
			'type' => 'integer',
			'visible' => ['index'=>true]
		]);


		$this->setFieldOrder([
			'name', 'education_subject_id', 'teachers', 'male_students', 'female_students',
		]);

		$academicPeriodOptions = $this->getAcademicPeriodOptions($extra['institution_id']);
		if (empty($academicPeriodOptions)) {
			$this->Alert->warning('InstitutionSubjects.noPeriods');
		}

		if (empty($this->request->query['academic_period_id'])) {
			$this->request->query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
		}
		$extra['selectedAcademicPeriodId'] = $this->queryString('academic_period_id', $academicPeriodOptions);
		$extra['selectedClassId'] = 0;
	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
    public function indexBeforeAction(Event $event, ArrayObject $extra) {
		$Classes = $this->InstitutionClasses;
		$Subjects = $this;

		$academicPeriodOptions = $this->AcademicPeriods->getList();
		$institutionId = $extra['institution_id'];
		$selectedAcademicPeriodId = $extra['selectedAcademicPeriodId'];

		$this->advancedSelectOptions($academicPeriodOptions, $selectedAcademicPeriodId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
			'callable' => function($id) use ($Classes, $institutionId) {
				return $Classes->find()->where([
					$Classes->aliasField('institution_id') => $institutionId,
					$Classes->aliasField('academic_period_id') =>  $id
				])->count();
			}
		]);

		$classOptions = $Classes->find('list')
								->where([
									$Classes->aliasField('academic_period_id') => $selectedAcademicPeriodId,
									$Classes->aliasField('institution_id') => $institutionId
								])
								->toArray();
		if (empty($classOptions)) {
			$this->Alert->warning('Institutions.noClassRecords');
		}
		$selectedClassId = $this->queryString('class_id', $classOptions);
		$this->advancedSelectOptions($classOptions, $selectedClassId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noSubjects')),
			'callable' => function($id) use ($Subjects, $institutionId, $selectedAcademicPeriodId) {
				$query = $Subjects->find()
								->join([
									[
										'table' => 'institution_class_subjects',
										'alias' => 'InstitutionClassSubjects',
										'conditions' => [
											'InstitutionClassSubjects.institution_subject_id = ' . $Subjects->aliasField('id'),
											'InstitutionClassSubjects.institution_class_id' => $id
										]
									]
								])
								->where([
									$Subjects->aliasField('institution_id') => $institutionId,
									$Subjects->aliasField('academic_period_id') => $selectedAcademicPeriodId,
								]);
				return $query->count();
			}
		]);
		
		$extra['elements']['control'] = [
			'name' => 'Institution.Subjects/controls', 
			'data' => [
				'academicPeriodOptions' => $academicPeriodOptions,
				'classOptions' => $classOptions, 
				'selectedClass' => $selectedClassId, 
			],
			'options' => [],
			'order' => 3
		];
		$extra['selectedClassId'] = $selectedClassId;
	}

    public function findByClasses(Query $query, array $options) {
    	return $query
			->join([
				[
					'table' => 'institution_class_subjects',
					'alias' => 'InstitutionClassSubjects',
					'conditions' => [
						'InstitutionClassSubjects.institution_subject_id = InstitutionSubjects.id',
						'InstitutionClassSubjects.institution_class_id' => $options['selectedClassId']
					]
				]
			])
			;
    }

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$query
		->find('byClasses', ['selectedClassId' => $extra['selectedClassId']])
		->contain(['Teachers'])
		->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']]);
	}

	public function indexAfterAction(Event $event, ResultSet $data, ArrayObject $extra) {

		if (isset($extra[$this->aliasField('notice')]) && !empty($extra[$this->aliasField('notice')])) {
			$this->Alert->warning($extra[$this->aliasField('notice')], ['reset'=>true]);
			unset($extra[$this->aliasField('notice')]);
		}

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
				'action' => 'Subjects'
			]);
		}
		// $this->belongsToMany('Teachers', [
		// 	'className' => 'User.Users',
		// 	'through' => 'InstitutionSubjectStaff',
		// 	'conditions' => ['InstitutionSubjectStaff.status' => 1],
		// 	'targetForeignKey' => 'staff_id'
		// ]);
		$this->setFieldOrder([
			'academic_period_id', 'class_name', 'name', 'education_subject_code', 'education_subject_id', 'teachers', 'students',
		]);
	}

	public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$query->contain([
			'InstitutionClassSubjects.InstitutionClasses',
			'Teachers',
			'InstitutionSubjectStaff'
		]);
	}

	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$classes = [];
		foreach ($entity->institution_class_subjects as $key => $value) {
			if (is_object($value->institution_class)) {
				$classes[] = $value->institution_class->name;
			}
		}
		$entity->class_name = implode(', ', $classes);
		$students = $this
			->InstitutionSubjectStudents
			->find()
			->matching('Users.Genders')
			->innerJoin(['InstitutionClassStudent' => 'institution_class_students'], [
					'InstitutionClassStudent.institution_class_id = '.$this->InstitutionSubjectStudents->aliasField('institution_class_id'),
					'InstitutionClassStudent.student_id = '.$this->InstitutionSubjectStudents->aliasField('student_id')
				])
			->innerJoin(['StudentStatuses' => 'student_statuses'], [
					'InstitutionClassStudent.student_status_id = '. 'StudentStatuses.id'
				])
			->where([
				'InstitutionSubjectStudents.institution_subject_id'=>$entity->id
			])
			->select(['student_status' => 'StudentStatuses.name'])
			->autoFields(true)
			->toArray();
		$this->fields['students']['data']['students'] = $students;

		return $entity;
	}


/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
	public function addBeforeAction(Event $event, ArrayObject $extra) {
		$selectedAcademicPeriodId = $extra['selectedAcademicPeriodId'];
		if ($selectedAcademicPeriodId == -1) {
			return $this->controller->redirect([
				'plugin' => $this->controller->plugin, 
				'controller' => $this->controller->name, 
				'action' => 'Subjects'
			]);
		}

		$this->fields['name']['visible'] = false;
		$this->fields['teachers']['visible'] = false;
		$this->fields['students']['visible'] = false;
		$this->fields['education_subject_id']['visible'] = false;

		$this->fields['class_name']['visible'] = true;
		$this->fields['subjects']['visible'] = true;
		$this->setFieldOrder([
			'academic_period_id', 'class_name', 'subjects',
		]);

		$Classes = $this->InstitutionClasses;

		$institutionId = $extra['institution_id'];
		$periodOption = ['' => '-- ' . __('Select Period') .' --'];
		$academicPeriodOptions = $this->AcademicPeriods->getlist(['isEditable'=>true]);
		$academicPeriodOptions = $periodOption + $academicPeriodOptions;

		if ($this->request->is(['post', 'put']) && $this->request->data($this->aliasField('academic_period_id'))) {
			$extra['selectedAcademicPeriodId'] = $this->request->data($this->aliasField('academic_period_id'));
			$selectedAcademicPeriodId = $extra['selectedAcademicPeriodId'];
		}

		$this->advancedSelectOptions($academicPeriodOptions, $selectedAcademicPeriodId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
			'callable' => function($id) use ($Classes, $institutionId) {
				return $Classes->find()->where([
					$Classes->aliasField('institution_id') => $institutionId,
					$Classes->aliasField('academic_period_id') =>  $id
				])->count();
			}
		]);

		$classOptions = $Classes->find('list')
								->where([
									$Classes->aliasField('academic_period_id') => $selectedAcademicPeriodId, 
									$Classes->aliasField('institution_id') => $institutionId
								])
								->toArray();
		$ClassGrades = $this->InstitutionClassGrades;
		$selectedClassId = $this->postString('class_name', $classOptions);
		$this->advancedSelectOptions($classOptions, $selectedClassId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
			'callable' => function($id) use ($ClassGrades) {
				return $ClassGrades->find()->where([
					$ClassGrades->aliasField('institution_class_id') => $id
				])->count();
			}
		]);
		$extra['selectedClassId'] = $selectedClassId;

		$this->fields['academic_period_id']['options'] = $academicPeriodOptions;
		$this->fields['class_name']['options'] = $classOptions;
	}

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra) {
		$process = function ($model, $entity) use ($data, $extra) {
			list($error, $subjects, $data) = $model->prepareEntityObjects($model, $data, $extra);
			if (!$error && $subjects) {
				foreach ($subjects as $subject) {
			    	$model->save($subject);
				}
				$extra[$this->aliasField('notice')] = 'passed';
				return true;
			} else {
				if ($error == $this->aliasField('allSubjectsAlreadyAdded')) {
					$extra[$this->aliasField('notice')] = $this->aliasField('allSubjectsAlreadyAdded');
					return true;
				} else {
					$model->log($error, 'debug');
					if (is_array($error)) {
						$model->Alert->error('general.add.failed');
					} else {
						/**
						 * unset all field validation except for "institution_id" to trigger validation error in ControllerActionComponent
						 */
						foreach ($model->fields as $value) {
							if ($value['field'] != 'institution_id') {
								$model->validator()->remove($value['field']);
							}
						}
						$model->Alert->error($error);
					}
					$model->request->data = $data;
					return false;
				}
			}
		};
		return $process;
	}

	public function addAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra) {
		if (isset($extra[$this->aliasField('notice')]) && !empty($extra[$this->aliasField('notice')])) {
			$notice = $extra[$this->aliasField('notice')];
			unset($extra[$this->aliasField('notice')]);
			if ($notice=='passed') {
				$this->Alert->success('general.add.success', ['reset'=>true]);
			} else {
				$this->Alert->warning($notice, ['reset'=>true]);
			}
			return $this->controller->redirect($this->url('index', 'QUERY'));
		}
	}

	public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$query = $this
				->Institutions
				->Staff
				->find()
				->contain(['Users'])
				->where(['Staff.institution_id' => $extra['institution_id']])
				->toArray();
		$teachers = [0=>'-- ' . __('Select Teacher or Leave Blank') . ' --'];
		foreach ($query as $key => $value) {
			if ($value->has('user')) {
				$teachers[$value->user->id] = $value->user->name;
			}
		}
		$subjects = $this->getSubjectOptions($extra['selectedClassId']);
		$existedSubjects = $this->getExistedSubjects($extra['selectedClassId'], true);
		$this->fields['subjects']['data'] = [
			'teachers' => $teachers,
			'subjects' => $subjects,
			'existedSubjects' => $existedSubjects
		];
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
				'action' => 'Subjects'
			]);
		}

		$this->setFieldOrder([
			'name', 'no_of_seats', 
			'academic_period_id', 'education_subject_id', 
			'teachers', 'students',
		]);
	}

	public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$query->contain([
			'AcademicPeriods', 
			'EducationSubjects',
			'Teachers',
			'InstitutionSubjectStaff',
			'InstitutionSubjectStudents.Users.Genders',
			'InstitutionClassSubjects'
		]);
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra) {
		// pr($entity);
		// pr($data);
		/**
		 * Unable to utilise updateAll for this scenario.
		 * Only new student records will be saved as status=1 at the later part of this scope.
		 * Existitng records which is not removed from the UI list, will remain as status=0 instead of 1.
		 */
		// $this->InstitutionSubjectStudents->updateAll(['status'=>0], ['institution_subject_id' => $entity->id]);
		// $this->InstitutionSubjectStaff->updateAll(['status'=>0], ['institution_subject_id' => $entity->id]);

		/**
		 * In students.ctp, we set the staff_id as the array keys for easy search and compare.
		 * Assign back original record's id to the new list so as to preserve id numbers.
		 */
		foreach($entity->institution_subject_students as $key => $record) {
			$k = $record->student_id;
			if (array_key_exists('institution_subject_students', $data[$this->alias()])) {
				if (!array_key_exists($k, $data[$this->alias()]['institution_subject_students'])) {			
					$data[$this->alias()]['institution_subject_students'][$k] = [
						'id' => $record->id,
						'status' => 0 
					];
				} else {
					$data[$this->alias()]['institution_subject_students'][$k]['id'] = $record->id;
				}
			} else {
				$data[$this->alias()]['institution_subject_students'][$k] = [
					'id' => $record->id,
					'status' => 0 
				];
			}
		}
		$checkedStaff = [];
		foreach($entity->institution_subject_staff as $key => $record) {
			$k = $record->staff_id;
			if (	array_key_exists('teachers', $data[$this->alias()])	
				&& 	array_key_exists('_ids', $data[$this->alias()]['teachers'])
				&&  !empty($data[$this->alias()]['teachers']['_ids'])
			) {
				if (!in_array($k, $data[$this->alias()]['teachers']['_ids'])) {
					$data[$this->alias()]['institution_subject_staff'][$k] = [
						'id' => $record->id,
						'status' => 0 
					];
				} else {
					$checkedStaff[] = $k;
					$data[$this->alias()]['institution_subject_staff'][$k] = [
						'id' => $record->id,
						'staff_id' => $k,
						'status' => 1
					];
				}
			} else {
				$data[$this->alias()]['institution_subject_staff'][$k] = [
					'id' => $record->id,
					'status' => 0 
				];
			}
		}
		if (	array_key_exists('teachers', $data[$this->alias()])	
				&& 	array_key_exists('_ids', $data[$this->alias()]['teachers'])
				&&  !empty($data[$this->alias()]['teachers']['_ids'])
			) {
			$balance = array_diff($data[$this->alias()]['teachers']['_ids'], $checkedStaff);
			foreach ($balance as $bal) {
				$data[$this->alias()]['institution_subject_staff'][$bal] = [
					'staff_id' => $bal,
					'status' => 1
				];
			}
		}
		unset($data[$this->alias()]['teachers']);
	}

	/**
	 * Changed in PHPOE-1780 test fail re-work. major modification.
	 * @var [type]
	 */
	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$extra['selectedAcademicPeriodId'] = $entity->academic_period_id;
		$InstitutionSubjectStudentsTable = $this->InstitutionSubjectStudents;
		$students = $InstitutionSubjectStudentsTable->find()->contain(['Users.Genders'])
			->innerJoin(['InstitutionClassStudent' => 'institution_class_students'], [
					'InstitutionClassStudent.institution_class_id = '.$InstitutionSubjectStudentsTable->aliasField('institution_class_id'),
					'InstitutionClassStudent.student_id = '.$InstitutionSubjectStudentsTable->aliasField('student_id')
				])
			->innerJoin(['StudentStatuses' => 'student_statuses'], [
					'InstitutionClassStudent.student_status_id = '. 'StudentStatuses.id'
				])
			->where([
				'InstitutionSubjectStudents.institution_subject_id'=>$entity->id
			])
			->select(['student_status' => 'StudentStatuses.name'])
			->autoFields(true)
			->toArray()
			;
		$collection = new Collection($students);
		$recordedStudentIds = (new Collection($collection->toArray()))->combine('student_id', 'status')->toArray();
		$teacherOptions = $this->getTeacherOptions($entity);

		/**
		 * Check if the request is a page reload
		 * Populate records in the UI table & unset the record from studentOptions
		 * Changed in PHPOE-1799-2 for PHPOE-1780. convert security_users_id to student_id
		 */
		$includedStudents = [];
		if (count($this->request->data)>0 && $this->request->data['submit']=='add') {
			$studentOptions = $this->getStudentsOptions($entity);
			/**
			 * Populate records in the UI table & unset the record from studentOptions
			 * Changed in PHPOE-1799-2 for PHPOE-1780. convert security_users_id to student_id
			 */
			if (array_key_exists('institution_subject_students', $this->request->data[$this->alias()])) {
				foreach ($this->request->data[$this->alias()]['institution_subject_students'] as $row) {
					if ($row['status']>0 && array_key_exists($row['student_id'], $studentOptions)) {
						$id = $row['student_id'];
						/**
						 * Changed in PHPOE-1997 to remove duplicate records on the UI.
						 * Attempt to improve performance by not creating an entity with User record attached [@see $this->createVirtualEntity()],
						 * since student record with its User record attached already exists in the $students array.
						 */
						if (!array_key_exists($id, $recordedStudentIds)) {
							$students[] = $this->createVirtualEntity($id, $entity, 'students');
						}
						unset($studentOptions[$id]);
					}
				}
			}

			/**
			 * Insert the newly added record into the UI table & unset the record from studentOptions
			 */
			if (array_key_exists('student_id', $this->request->data) && $this->request->data['student_id']>-1) {
				$id = $this->request->data['student_id'];
				/**
				 * Changed in PHPOE-1780. Includes option to add all student available in the dropdown list
				 */
				if ($id==0) {
					foreach ($studentOptions as $key=>$value) {
						if ($key>0) {
							$student = $this->createVirtualEntity($key, $entity, 'students');
							if ( !empty( $student->user ) ) {
								$students[] = $student;
							}
							unset($studentOptions[$key]);
						}
					}
				} else {
					/**
					 * @todo modify this to improve performance by not creating an entity with User record attached [@see $this->createVirtualEntity()],
					 * IF student record with its User record attached already exists in the $students array.
					 * Try to change the status attribute to true instead?
					 */
					$student = $this->createVirtualEntity($id, $entity, 'students');
					if ( !empty( $student->user ) ) {
						$students[] = $student;
					}
					unset($studentOptions[$id]);
				}
			}

		} else {
			foreach ($recordedStudentIds as $key => $value) {
				if ($value>0) {
					$includedStudents[] = $key;
				}
			}
			$studentOptions = $this->getStudentsOptions($entity, $includedStudents);
		}

		/**
		 * Changed in PHPOE-1780 test fail re-work. if there are no more available students, change the default options in the select field.
		 */
		if (count($studentOptions)==2) {
			$studentOptions = ['-1' => $this->getMessage('Users.select_student_empty')];
		}

		if (!empty($teacherOptions)) {
			$this->fields['teachers']['options'] = $teacherOptions;
		}
		$this->fields['students']['data'] = [
			'students' => $students,
			'studentOptions' => $studentOptions
		];
	
		$this->fields['academic_period_id']['type'] = 'readonly';
		$this->fields['academic_period_id']['attr']['value'] = $this->getAcademicPeriodOptions($extra['institution_id'])[$entity->academic_period_id];
		
		/**
		 * Changed in PHPOE-1780 test fail re-work. Get Education Subject name directly from EducationSubjects table since there is only one $entity->education_subject_id.
		 */
		$this->fields['education_subject_id']['type'] = 'readonly';
		$this->fields['education_subject_id']['attr']['value'] = $this->EducationSubjects->get($entity->education_subject_id)->name;
	
		return $entity;
	}


/******************************************************************************************************************
**
** essential functions
**
******************************************************************************************************************/
	public function prepareEntityObjects($model, ArrayObject $data, ArrayObject $extra) {
		$commonData = $data['InstitutionSubjects'];
		$error = false;
		$subjects = false;
		$subjectOptions = $this->getSubjectOptions($extra['selectedClassId']);
		$existedSubjects = $this->getExistedSubjects($extra['selectedClassId'], true);
		if (count($subjectOptions) == count($existedSubjects)) {
			$error = $this->aliasField('allSubjectsAlreadyAdded');
		} else if (isset($data['MultiSubjects']) && count($data['MultiSubjects'])>0) {
			foreach ($data['MultiSubjects'] as $key=>$row) {
				if (isset($row['education_subject_id']) && isset($row['institution_subject_staff'])) {
					$subjectSelected = true;
					$subjects[$key] = [
						'key' => $key,
						'name' => $row['name'],
						'education_subject_id' => $row['education_subject_id'],
						'academic_period_id' => $commonData['academic_period_id'],
						'institution_id' => $commonData['institution_id'],
						'institution_class_subjects' => [
							[
								'status' => 1,
								'institution_class_id' => $commonData['class_name']
							]
						]
					];
					if ($row['institution_subject_staff'][0]['staff_id']!=0) {
						$subjects[$key]['institution_subject_staff'] = $row['institution_subject_staff'];
					}
				}
			}
			if (!$subjects) {
				$error = $this->aliasField('noSubjectSelected');
			} else {
				$subjects = $model->newEntities($subjects);
				/**
				 * check individual entity for any error
				 */
				foreach ($subjects as $subject) {
				    if ($subject->errors()) {
				    	$error = $subject->errors();
				    	$data['MultiSubjects'][$subject->key]['errors'] = $error;
				    }
				}
			}
		} else {
			// $this->log(__FILE__.' @ '.__LINE__.': noSubjectsInClass', 'debug');
			$error = $this->aliasField('noSubjectsInClass');
		}
		return [$error, $subjects, $data];
	}

	public function createVirtualEntity($id, $entity, $persona, $requestData = false) {
		if (isset($entity->toArray()['institution_class_subjects'])) {
			$classId = $entity->toArray()['institution_class_subjects'][0]['institution_class_id'];
		} else {
			$classId = $entity->toArray()['institution_classes'][0]['id'];
		}
		if (strtolower($persona)=='students') {
			$userData = $this->Institutions->Students->find()->contain(['Users.Genders', 'StudentStatuses'])->where(['student_id'=>$id])->first();
			$data = [
				'id'=>$this->getExistingRecordId($id, $entity, $persona),
				'student_id'=>$id,
				'institution_subject_id'=>$entity->id,
				'institution_class_id'=>$classId,
				'status'=>1,
				'student_status' => $userData->student_status->name,
				'user'=>[]
			];
			if (!empty($requestData)) {
				if (array_key_exists('education_grade_id', $requestData)) {
					$data['education_grade_id'] = $requestData['education_grade_id'];
				}
				if (array_key_exists('status', $requestData)) {
					$data['status'] = $requestData['status'];
				}
			}
		} else {
			$userData = $this->Institutions->Staff->find()->contain(['Users'=>['Genders']])->where(['staff_id'=>$id])->first();
			$data = [
				'id'=>$this->getExistingRecordId($id, $entity, $persona),
				'staff_id'=>$id,
				'institution_subject_id'=>$entity->id,
				'institution_class_id'=>$classId,
				'status'=>1,
				'user'=>[]
			];
		}
		if (empty($userData)) {
			$this->Alert->warning($this->alias().".studentRemovedFromInstitution");
		} else {
			$model = 'InstitutionSubject'.ucwords(strtolower($persona));
			$newEntity = $this->$model->newEntity();
			$newEntity = $this->$model->patchEntity($newEntity, $data);
			$newEntity->user = $userData->user;
			return $newEntity;
		}
	}

	protected function getExistingRecordId($id, $entity, $persona) {
		$recordId = '';
		$relationKey = 'institution_subject_'.strtolower($persona);
		foreach ($entity->$relationKey as $data) {
			if (strtolower($persona)=='students') {
				if ($data->student_id == $id) {
					$recordId = $data->id;
				}
			} else {
				if ($data->staff_id == $id) {
					$recordId = $data->id;
				}
			}
		}
		return $recordId;
	}

	private function getAcademicPeriodOptions($institutionId) {
		$InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
		$conditions = [$InstitutionGrades->aliasField('institution_id') => $institutionId];
		return $InstitutionGrades->getAcademicPeriodOptions($this->Alert, $conditions);
	}
	
	private function getSubjectOptions($selectedClassId, $listOnly=false) {
		$Grade = $this->InstitutionClassGrades;
		$gradeOptions = $Grade->find()
							->contain('EducationGrades')
							->where([
								$Grade->aliasField('institution_class_id') => $selectedClassId,
								$Grade->aliasField('status') => 1
							])
							->toArray();
		$gradeData = [];
		foreach ($gradeOptions as $key => $value) {
			$gradeData[$value->education_grade->id] = $value->education_grade->name;
		}

		$EducationGradesSubjects = TableRegistry::get('Education.EducationGradesSubjects');
		/**
		 * Do not check for the visible attribute in sql query,
		 * message the data in the view file instead so that we could counter-check for
		 * subjects that are already created in the institution.
		 */
		$query = $EducationGradesSubjects
				->find()
				->contain(['EducationSubjects'])
				->where([
					'EducationGradesSubjects.education_grade_id IN ' => array_keys($gradeData),
				]);
		$subjects = $query
				->order('EducationSubjects.order')
				->group('EducationSubjects.id')
				->toArray();
		if ($listOnly) {
			$subjectList = [];
			foreach ($subjects as $key => $value) {
				$subjectList[$value->id] = $value->education_subject->name;
			}
			$data = $subjectList;
		} else {
			$data = $subjects;
		}
		if (empty($data)) {
			// $this->log(__FILE__.' @ '.__LINE__.': noSubjectsInClass', 'debug');
			$this->Alert->warning('Institution.Institutions.noSubjectsInClass');
		}
		return $data;
	}

	private function getExistedSubjects($selectedClassId, $listOnly=false) {
		$subjects = $this
			->InstitutionClassSubjects
			->find()
			->contain([
				'InstitutionSubjects'=>[
					'EducationSubjects',
					'Teachers.Genders'
				],
			])
			->where([
				'InstitutionClassSubjects.institution_class_id' => $selectedClassId,
				'InstitutionClassSubjects.status' => 1
			])
			->toArray();
		if ($listOnly) {
			$subjectList = [];
			foreach ($subjects as $key => $value) {
				$subjectList[$value->institution_subject->education_subject->id] = [
					'name' => $value->institution_subject->name,
					'subject_name' => $value->institution_subject->education_subject->name,
					'teachers' => $value->institution_subject->teachers
				];
			}
			$data = $subjectList;
		} else {
			$data = $subjects;
		}
		return $data;
	}

	/**
	 * @todo should have additional filter; by start_date, end_date,
	 */
	protected function getTeacherOptions($entity) {
		// $academicPeriodObj = $this->AcademicPeriods->get($entity->academic_period_id);
		// $startDate = $this->AcademicPeriods->getDate($academicPeriodObj->start_date);
		// $endDate = $this->AcademicPeriods->getDate($academicPeriodObj->end_date);

        $Staff = TableRegistry::get('Institution.Staff');
		$query = $Staff->find('all')
						->find('withBelongsTo')
						->find('byInstitution', ['Institutions.id' => $entity->institution_id])
						->find('byPositions', ['Institutions.id' => $entity->institution_id, 'type' => 1]) // refer to OptionsTrait for type options
						->find('AcademicPeriod', ['academic_period_id'=>$entity->academic_period_id])
						->where([
							$Staff->aliasField('institution_position_id') 
						])
						;
		$options = [];
		foreach ($query->toArray() as $key => $value) {
			if ($value->has('user')) {
				$options[$value->user->id] = $value->user->name_with_id;
			}
		}
		return $options;
	}

	/**
	 * Changed in PHPOE-1780 test fail re-work. major modification.
	 * Previously, the grades where populated based on a selected classId.
	 * Those students who matched one of the grades will be included in the list.
	 *
	 * Since there will be more than one class where a subject could be linked to, the logic is changed to populate
	 * students using a longer route to obtain the grades for the current academic period.
	 * student_status_id = 1 is also included.
	 * @var integer
	 * @return array list of students
	 *
	 * @todo  modify the search to increase performance
	 */
	protected function getStudentsOptions($entity, $includedStudents = []) {
		// from $entity, you can get the subject_id which you can use it to retrieve the list of grade_id from education_grades_subjects
		// from the list of grade_ids, you will use it to find the list of students from institution_class_students using grade_id and the class keys as conditions 
		$classKeys = [];
		foreach ($entity->institution_class_subjects as $classSubjects) {
			$classKeys[] = $classSubjects->institution_class_id;
		}
		$EducationGradesSubjects = TableRegistry::get('Education.EducationGradesSubjects');
		$grades = $EducationGradesSubjects
			->find('list', [
				'keyField' => 'id',
    			'valueField' => 'education_grade_id'
    		])
			->where([
				$EducationGradesSubjects->aliasField('education_subject_id') => $entity->education_subject_id,
				$EducationGradesSubjects->aliasField('visible') => 1
			])
			->toArray();

		$Students = TableRegistry::get('Institution.InstitutionClassStudents');
		$conditions = [
			$Students->aliasField('institution_class_id').' IN' => $classKeys,
			$Students->aliasField('education_grade_id').' IN' => $grades
		];
		/**
		 * Attempt to improve performance by filtering out includedStudents in $studentOptions through SQL query
		 */
		if (!empty($includedStudents)) {
			$conditions[$Students->aliasField('student_id').' NOT IN'] = $includedStudents;
		}

		$query = $Students
			->find('all')
			->matching('Users')
			->where( $conditions )
			->toArray();

		/**
		 * default $studentOptions options
		 */
		$studentOptions = ['-1' => $this->getMessage('Users.select_student'), '0' => $this->getMessage('Users.add_all_student')];		
		foreach ($query as $student) {
			if ($student->has('_matchingData')) {
				$user = $student->_matchingData['Users'];
				if (!$this->InstitutionStudents->exists([$this->InstitutionStudents->aliasField('student_id') => $user->id])) {
					$this->log('Data corrupted with no institution student: '. $student->id . ' @ '. $this->registryAlias() .': '. __LINE__, 'debug');
				} else {
					$studentOptions[$user->id] = $user->name_with_id;
				}
			} else {
				$this->log('Data corrupted with no security user for student: '. $student->id, 'debug');
			}
		}
		return $studentOptions;
	}

}
