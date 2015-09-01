<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Collection\Collection;

use App\Model\Table\AppTable;

class InstitutionSiteClassesTable extends AppTable {
	public $institutionId = 0;
	public $selectedSectionId = 0;
	private $public = 0;
	private $_academicPeriodOptions = [];
	private $_selectedAcademicPeriodId = -1;

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', 			['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', 				['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('EducationSubjects', 			['className' => 'Education.EducationSubjects']);
		
		$this->hasMany('InstitutionSiteSectionClasses', ['className' => 'Institution.InstitutionSiteSectionClasses']);
		$this->hasMany('InstitutionSiteClassStudents', 	['className' => 'Institution.InstitutionSiteClassStudents']);
		$this->hasMany('InstitutionSiteClassStaff', 	['className' => 'Institution.InstitutionSiteClassStaff']);

		$this->belongsToMany('InstitutionSiteSections', [
			'className' => 'Institution.InstitutionSiteSections',
			'joinTable' => 'institution_site_section_classes',
			'foreignKey' => 'institution_site_class_id',
			'targetForeignKey' => 'institution_site_section_id'
		]);

		$this->belongsToMany('Teachers', [
			'className' => 'User.Users',
			'through' => 'InstitutionSiteClassStaff',
			'conditions' => ['InstitutionSiteClassStaff.status' => 1],
			'foreignKey' => 'institution_site_class_id',
			'targetForeignKey' => 'security_user_id'
		]);

		// this behavior restricts current user to see All Subjects or My Subjects
		$this->addBehavior('Security.InstitutionSubject');
		
		// $this->belongsToMany('InstitutionSiteSections', ['through' => 'InstitutionSiteSectionClasses']);

		/**
		 * Short cuts to initialised models set in relations.
		 * By using initialised models set in relations, the relation's className is set at a single place.
		 * In add operations, these models attributes are empty by default.
		 */
		$this->InstitutionSiteSections = $this->Institutions->InstitutionSiteSections;
		$this->InstitutionSiteSectionGrades = $this->InstitutionSiteSectionClasses->InstitutionSiteSections->InstitutionSiteSectionGrades;

		$this->addBehavior('AcademicPeriod.AcademicPeriod');
	}

	public function validationDefault(Validator $validator) {
		$validator->requirePresence('name');
		return $validator;
	}

	public function beforeAction($event) {
    	$this->ControllerAction->field('academic_period_id', ['type' => 'select', 'visible' => ['view'=>true, 'edit'=>true, 'add'=>true], 'onChangeReload' => true]);
    	$this->ControllerAction->field('created', ['type' => 'string', 'visible' => false]);
    	$this->ControllerAction->field('created_user_id', ['type' => 'string', 'visible' => false]);
		$this->ControllerAction->field('education_subject_code', ['type' => 'string', 'visible' => ['view'=>true]]);
		$this->ControllerAction->field('education_subject_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('modified', ['type' => 'string', 'visible' => false]);
    	$this->ControllerAction->field('modified_user_id', ['type' => 'string', 'visible' => false]);
    	$this->ControllerAction->field('name', ['type' => 'string', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
		$this->ControllerAction->field('no_of_seats', ['type' => 'integer', 'attr'=>['min' => 1], 'visible' => false]);
		$this->ControllerAction->field('class_name', ['type' => 'select', 'visible' => ['view'=>true], 'onChangeReload' => true]);

		$this->ControllerAction->field('students', [
			'label' => '',
			'override' => true,
			'type' => 'element',
			'element' => 'Institution.Classes/students',
			'data' => [	
				'students'=>[],
				'studentOptions'=>[],
				'categoryOptions'=>[],
				// 'isHistoryRecord'=>false
			],
			'visible' => ['view'=>true, 'edit'=>true]
		]);
		$this->ControllerAction->field('classes', [
			'label' => '',
			'type' => 'element',
			'element' => 'Institution.Classes/classes',
			'data' => [	
				'subjects'=>[],
				'teachers'=>[]
			],
			'visible' => false
		]);

		$this->ControllerAction->field('teachers', [
			'type' => 'chosenSelect',
			'fieldNameKey' => 'teachers',
			'fieldName' => $this->alias() . '.teachers._ids',
			'placeholder' => $this->getMessage('Users.select_teacher'),
			'valueWhenEmpty' => 'No Teacher Assigned',
			'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]
		]);

		$this->ControllerAction->field('male_students', [
			'type' => 'integer',
			'visible' => ['index'=>true]
		]);
		$this->ControllerAction->field('female_students', [
			'type' => 'integer',
			'visible' => ['index'=>true]
		]);


		$this->ControllerAction->setFieldOrder([
			'name', 'education_subject_id', 'teachers', 'male_students', 'female_students',
		]);

		$this->_academicPeriodOptions = $this->getAcademicPeriodOptions();
		if (empty($this->_academicPeriodOptions)) {
			$this->Alert->warning('Institutions.noProgrammes');
		}

		if (empty($this->request->query['academic_period_id'])) {
			$this->request->query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
		}
		$this->_selectedAcademicPeriodId = $this->queryString('academic_period_id', $this->_academicPeriodOptions);
	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
    public function indexBeforeAction(Event $event) {
		$Classes = $this;
		$Sections = $this->InstitutionSiteSections;

		$academicPeriodOptions = $this->AcademicPeriods->getList();
		$institutionId = $this->institutionId;

		$this->advancedSelectOptions($academicPeriodOptions, $this->_selectedAcademicPeriodId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noSections')),
			'callable' => function($id) use ($Sections, $institutionId) {
				return $Sections->findByInstitutionSiteIdAndAcademicPeriodId($institutionId, $id)->count();
			}
		]);

		$sectionOptions = $Sections->find('list')
									->where([
										'academic_period_id'=>$this->_selectedAcademicPeriodId, 
										'institution_site_id'=>$institutionId
									])
									->toArray();
		$selectedAcademicPeriodId = $this->_selectedAcademicPeriodId;
		if (empty($sectionOptions)) {
			$this->Alert->warning('Institutions.noSections');
		}
		$this->selectedSectionId = $this->queryString('section_id', $sectionOptions);
		$this->advancedSelectOptions($sectionOptions, $this->selectedSectionId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
			'callable' => function($id) use ($Classes, $institutionId, $selectedAcademicPeriodId) {
				$query = $Classes->find()
									->join([
										[
											'table' => 'institution_site_section_classes',
											'alias' => 'InstitutionSiteSectionClass',
											'conditions' => [
												'InstitutionSiteSectionClass.institution_site_class_id = InstitutionSiteClasses.id',
												'InstitutionSiteSectionClass.institution_site_section_id' => $id
											]
										]
									])
									->where([
										$Classes->aliasField('institution_site_id') => $institutionId,
										$Classes->aliasField('academic_period_id') => $selectedAcademicPeriodId,
									]);
				return $query->count();
			}
		]);

		$toolbarElements = [
            ['name' => 'Institution.Classes/controls', 
             'data' => [
	            	'academicPeriodOptions'=>$academicPeriodOptions,
	            	'sectionOptions'=>$sectionOptions, 
	            	'selectedSection'=>$this->selectedSectionId, 
	            ],
	         'options' => []
            ]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);

	}

    public function findBySections(Query $query, array $options) {
    	return $query
			->join([
				[
					'table' => 'institution_site_section_classes',
					'alias' => 'InstitutionSiteSectionClass',
					'conditions' => [
						'InstitutionSiteSectionClass.institution_site_class_id = InstitutionSiteClasses.id',
						'InstitutionSiteSectionClass.institution_site_section_id' => $this->selectedSectionId
					]
				]
			])
			;
    }

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query
		->find('bySections')
		->contain(['Teachers'])
		->where([$this->aliasField('academic_period_id') => $this->_selectedAcademicPeriodId]);
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
		// $this->belongsToMany('Teachers', [
		// 	'className' => 'User.Users',
		// 	'through' => 'InstitutionSiteClassStaff',
		// 	'conditions' => ['InstitutionSiteClassStaff.status' => 1],
		// 	'targetForeignKey' => 'security_user_id'
		// ]);
		$this->ControllerAction->setFieldOrder([
			'academic_period_id', 'class_name', 'name', 'education_subject_code', 'education_subject_id', 'teachers', 'students',
		]);
	}

	public function viewBeforeQuery(Event $event, Query $query) {
		$query->contain([
			'InstitutionSiteSectionClasses.InstitutionSiteSections',
			'Teachers',
			'InstitutionSiteClassStaff'
		]);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$sections = [];
		foreach ($entity->institution_site_section_classes as $key => $value) {
			if (is_object($value->institution_site_section)) {
				$sections[] = $value->institution_site_section->name;
			}
		}
		$entity->class_name = implode(', ', $sections);
		
		// pr($entity->teachers);
		// pr($this->fields['teachers']);

		$this->fields['students']['data']['students'] = $this
			->InstitutionSiteClassStudents
			->find()
			->contain(['Users'=>['Genders']])
			->where(['InstitutionSiteClassStudents.institution_site_class_id'=>$entity->id])
			->toArray();

		return $entity;
	}


/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
	public function addBeforeAction(Event $event) {
		if ($this->_selectedAcademicPeriodId == -1) {
			return $this->controller->redirect([
				'plugin' => $this->controller->plugin, 
				'controller' => $this->controller->name, 
				'action' => 'Classes'
			]);
		}

		$this->fields['name']['visible'] = false;
		$this->fields['teachers']['visible'] = false;
		$this->fields['students']['visible'] = false;
		$this->fields['education_subject_id']['visible'] = false;

		$this->fields['class_name']['visible'] = true;
		$this->fields['classes']['visible'] = true;
		$this->ControllerAction->setFieldOrder([
			'academic_period_id', 'class_name', 'classes',
		]);

		$Sections = $this->InstitutionSiteSections;

		$institutionId = $this->institutionId;
		$periodOption = ['' => '-- Select Period --'];
		$academicPeriodOptions = $this->AcademicPeriods->getlist();
		$academicPeriodOptions = $periodOption + $academicPeriodOptions;
		
		$this->advancedSelectOptions($academicPeriodOptions, $this->_selectedAcademicPeriodId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noSections')),
			'callable' => function($id) use ($Sections, $institutionId) {
				return $Sections->findByInstitutionSiteIdAndAcademicPeriodId($institutionId, $id)->count();
			}
		]);

		$sectionOptions = $Sections->find('list')
									->where([
										'academic_period_id'=>$this->_selectedAcademicPeriodId, 
										'institution_site_id'=>$this->institutionId
									])
									->toArray();
		$SectionGrades = $this->InstitutionSiteSectionGrades;
		$this->selectedSectionId = $this->postString('class_name', $sectionOptions);
		$this->advancedSelectOptions($sectionOptions, $this->selectedSectionId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
			'callable' => function($id) use ($SectionGrades) {
				return $SectionGrades->findByInstitutionSiteSectionId($id)->count();
			}
		]);

		$this->fields['academic_period_id']['options'] = $academicPeriodOptions;
		$this->fields['class_name']['options'] = $sectionOptions;

	}

	public function prepareEntityObjects($model, ArrayObject $data) {
		$commonData = $data['InstitutionSiteClasses'];
		$error = false;
		$classes = false;
		if (isset($data['MultiClasses']) && count($data['MultiClasses'])>0) {
			foreach ($data['MultiClasses'] as $key=>$row) {
				if (isset($row['education_subject_id']) && isset($row['institution_site_class_staff'])) {
					$subjectSelected = true;
					$classes[$key] = [
						'key' => $key,
						'name' => $row['name'],
						'education_subject_id' => $row['education_subject_id'],
						'academic_period_id' => $commonData['academic_period_id'],
						'institution_site_id' => $commonData['institution_site_id'],
						'institution_site_section_classes' => [
							[
								'status' => 1,
								'institution_site_section_id' => $commonData['class_name']
							]
						]
					];
					if ($row['institution_site_class_staff'][0]['security_user_id']!=0) {
						$classes[$key]['institution_site_class_staff'] = $row['institution_site_class_staff'];
					}
				}
			}
			if (!$classes) {
				$error = 'Institution.Institutions.noSubjectSelected';
			} else {
				$classes = $model->newEntities($classes);
				/**
				 * check individual entity for any error
				 */
				foreach ($classes as $class) {
				    if ($class->errors()) {
				    	$error = $class->errors();
				    	$data['MultiClasses'][$class->key]['errors'] = $error;
				    }
				}
			}
		} else {
			// $this->log(__FILE__.' @ '.__LINE__.': noSubjectsInSection', 'debug');
			$error = 'Institution.Institutions.noSubjectsInSection';
		}
		return [$error, $classes, $data];
	}

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		$process = function ($model, $entity) use ($data) {
			list($error, $classes, $data) = $model->prepareEntityObjects($model, $data);
			// pr($data);die;
			if (!$error && $classes) {
				foreach ($classes as $class) {
			    	$model->save($class);
				}
				return true;
			} else {
				$model->log($error, 'debug');
				if (is_array($error)) {
					$model->Alert->error('general.add.failed');
				} else {
					/**
					 * unset all field validation except for "institution_site_id" to trigger validation error in ControllerActionComponent
					 */
					foreach ($model->fields as $value) {
						if ($value['field'] != 'institution_site_id') {
							$model->validator()->remove($value['field']);
						}
					}
					$model->Alert->error($error);
				}
				$model->request->data = $data;
				return false;
			}
		};
		return $process;
	}

	public function addAfterAction(Event $event, Entity $entity) {
		$query = $this
				->Institutions
				->InstitutionSiteStaff
				->find()
				->contain(['Users'])
				->where(['InstitutionSiteStaff.institution_site_id'=>$this->institutionId])
				->toArray();
		$teachers = [0=>'-- Select Teacher or Leave Blank --'];
		foreach ($query as $key => $value) {
			if ($value->has('user')) {
				$teachers[$value->user->id] = $value->user->name;
			}
		}
		$subjects = $this->getSubjectOptions();
		$existedSubjects = $this->getExistedSubjects(true);
		$this->fields['classes']['data'] = [
			'teachers' => $teachers,
			'subjects' => $subjects,
			'existedSubjects' => $existedSubjects
		];
		return $entity;
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

		$this->ControllerAction->setFieldOrder([
			'name', 'no_of_seats', 
			'academic_period_id', 'education_subject_id', 
			'teachers', 'students',
		]);
	}

	public function editBeforeQuery(Event $event, Query $query) {
		$query->contain([
			'AcademicPeriods', 
			'EducationSubjects',
			'Teachers',
			'InstitutionSiteClassStaff',
			'InstitutionSiteClassStudents.Users.Genders',
			'InstitutionSiteSectionClasses'
		]);
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// pr($entity);
		// pr($data);
		/**
		 * Unable to utilise updateAll for this scenario.
		 * Only new student records will be saved as status=1 at the later part of this scope.
		 * Existitng records which is not removed from the UI list, will remain as status=0 instead of 1.
		 */
		// $this->InstitutionSiteClassStudents->updateAll(['status'=>0], ['institution_site_class_id' => $entity->id]);
		// $this->InstitutionSiteClassStaff->updateAll(['status'=>0], ['institution_site_class_id' => $entity->id]);

		/**
		 * In students.ctp, we set the security_user_id as the array keys for easy search and compare.
		 * Assign back original record's id to the new list so as to preserve id numbers.
		 */
		foreach($entity->institution_site_class_students as $key => $record) {
			$k = $record->student_id;
			if (array_key_exists('institution_site_class_students', $data[$this->alias()])) {
				if (!array_key_exists($k, $data[$this->alias()]['institution_site_class_students'])) {			
					$data[$this->alias()]['institution_site_class_students'][$k] = [
						'id' => $record->id,
						'status' => 0 
					];
				} else {
					$data[$this->alias()]['institution_site_class_students'][$k]['id'] = $record->id;
				}
			} else {
				$data[$this->alias()]['institution_site_class_students'][$k] = [
					'id' => $record->id,
					'status' => 0 
				];
			}
		}
		$checkedStaff = [];
		foreach($entity->institution_site_class_staff as $key => $record) {
			$k = $record->security_user_id;
			if (	array_key_exists('teachers', $data[$this->alias()])	
				&& 	array_key_exists('_ids', $data[$this->alias()]['teachers'])
				&&  !empty($data[$this->alias()]['teachers']['_ids'])
			) {
				if (!in_array($k, $data[$this->alias()]['teachers']['_ids'])) {
					$data[$this->alias()]['institution_site_class_staff'][$k] = [
						'id' => $record->id,
						'status' => 0 
					];
				} else {
					$checkedStaff[] = $k;
					$data[$this->alias()]['institution_site_class_staff'][$k] = [
						'id' => $record->id,
						'security_user_id' => $k,
						'status' => 1
					];
				}
			} else {
				$data[$this->alias()]['institution_site_class_staff'][$k] = [
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
				$data[$this->alias()]['institution_site_class_staff'][$bal] = [
					'security_user_id' => $bal,
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
	public function editAfterAction(Event $event, Entity $entity) {
		// pr($this->AcademicPeriods->getCurrent());die;
		$this->_selectedAcademicPeriodId = $entity->academic_period_id;

		$students = $entity->institution_site_class_students;
		$collection = new Collection($students);
		$studentIds = $collection->extract('student_id')->toArray();
		// $studentIds = $collection->extract(function ($student) {
		// 				// pr();
		// 				return ($student->status != 0) ? $student->student_id : false;
		// 			})->toArray();

		$teacherOptions = $this->getTeacherOptions();
		$studentOptions = $this->getStudentsOptions($entity);

		// pr($students);
		/**
		 * Check if the request is a page reload
		 */
		// pr($students);die;
		// pr($collection->extract('student_id')->toArray());die;
		// pr(count($this->request->data['InstitutionSiteClasses']['institution_site_class_students']));die;
		if (count($this->request->data)>0 && $this->request->data['submit']=='add') {
			/**
			 * Populate records in the UI table & unset the record from studentOptions
			 * Changed in PHPOE-1799-2 for PHPOE-1780. convert security_users_id to student_id
			 */
			if (array_key_exists('institution_site_class_students', $this->request->data[$this->alias()])) {
				foreach ($this->request->data[$this->alias()]['institution_site_class_students'] as $row) {
					if ($row['status']>0 && array_key_exists($row['student_id'], $studentOptions)) {
						$id = $row['student_id'];
						if (!in_array($id, $studentIds) ) {
							$students[] = $this->createVirtualEntity($id, $entity, 'students');
							unset($studentOptions[$id]);
						}
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
				 * @var [type]
				 */
				if ($id==0) {
					foreach ($studentOptions as $key=>$value) {
						if ($key>0) {
							$students[] = $this->createVirtualEntity($key, $entity, 'students');
							unset($studentOptions[$key]);
						}
					}
				} else {
					$students[] = $this->createVirtualEntity($id, $entity, 'students');
					unset($studentOptions[$id]);
				}
			}
		}// else {
			/**
			 * Just unset the record from studentOptions on first page load
			 */
			foreach ($students as $row) {
				if ($row->status>0 && array_key_exists($row->student_id, $studentOptions)) {
					unset($studentOptions[$row->student_id]);
				}
			}
		// }

		/**
		 * Changed in PHPOE-1780 test fail re-work. if there are no more available students, change the options in nthe select field.
		 * @var [type]
		 */
		if (count($studentOptions)==2) {
			$studentOptions = ['-1' => $this->getMessage('Users.select_student_empty')];
		}
		if (!empty($teacherOptions)) {
			$this->fields['teachers']['options'] = $teacherOptions;
		}
		$this->fields['students']['data'] = [
			'students' => $students,
			'studentOptions' => $studentOptions,
			// 'isHistoryRecord' => ($this->AcademicPeriods->getCurrent() != $entity->academic_period_id) ? true : false
		];
	
		$this->fields['academic_period_id']['type'] = 'readonly';
		$this->fields['academic_period_id']['attr']['value'] = $this->getAcademicPeriodOptions()[$entity->academic_period_id];
		
		/**
		 * Changed in PHPOE-1780 test fail re-work. Get Education Subject name directly from EducationSubjects table since there is only one $entity->education_subject_id.
		 * @var [type]
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
	public function createVirtualEntity($id, $entity, $persona) {
		if (isset($entity->toArray()['institution_site_section_classes'])) {
			$sectionId = $entity->toArray()['institution_site_section_classes'][0]['institution_site_section_id'];
		} else {
			$sectionId = $entity->toArray()['institution_site_sections'][0]['id'];
		}
		if (strtolower($persona)=='students') {
			$userData = $this->Institutions->Students->find()->contain(['Users.Genders'])->where(['student_id'=>$id])->first();
			$data = [
				'id'=>$this->getExistingRecordId($id, $entity, $persona),
				'student_id'=>$id,
				'institution_site_class_id'=>$entity->id,
				'institution_site_section_id'=>$sectionId,
				'status'=>1,
				'user'=>[]
			];
		} else {
			$userData = $this->Institutions->InstitutionSiteStaff->find()->contain(['Users'=>['Genders']])->where(['security_user_id'=>$id])->first();
			$data = [
				'id'=>$this->getExistingRecordId($id, $entity, $persona),
				'security_user_id'=>$id,
				'institution_site_class_id'=>$entity->id,
				'institution_site_section_id'=>$sectionId,
				'status'=>1,
				'user'=>[]
			];
		}
		$model = 'InstitutionSiteClass'.ucwords(strtolower($persona));
		$newEntity = $this->$model->newEntity();
		$newEntity = $this->$model->patchEntity($newEntity, $data);
		$newEntity->user = $userData->user;
		return $newEntity;
	}

	protected function getExistingRecordId($id, $entity, $persona) {
		$recordId = '';
		$relationKey = 'institution_site_class_'.strtolower($persona);
		foreach ($entity->$relationKey as $data) {
			if (strtolower($persona)=='students') {
				if ($data->student_id == $id) {
					$recordId = $data->id;
				}
			} else {
				if ($data->security_user_id == $id) {
					$recordId = $data->id;
				}
			}
		}
		return $recordId;
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
	
	public function getSubjectOptions($listOnly=false) {
		$Grade = $this->InstitutionSiteSectionGrades;
		$gradeOptions = $Grade->find()
							->contain('EducationGrades')
							->where([
								$Grade->aliasField('institution_site_section_id') => $this->selectedSectionId,
								$Grade->aliasField('status') => 1
							])
							->toArray();
		$gradeData = [];
		foreach ($gradeOptions as $key => $value) {
			$gradeData[$value->education_grade->id] = $value->education_grade->name;
		}

		$EducationGradesSubjects = TableRegistry::get('Education.EducationGradesSubjects');
		$subjects = $EducationGradesSubjects
				->find()
				->contain(['EducationSubjects'])
				->where([
					'EducationGradesSubjects.education_grade_id IN ' => array_keys($gradeData),
					'EducationGradesSubjects.visible' => 1
				])
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
			// $this->log(__FILE__.' @ '.__LINE__.': noSubjectsInSection', 'debug');
			$this->Alert->warning('Institution.Institutions.noSubjectsInSection');
		}
		return $data;
	}

	private function getExistedSubjects($listOnly=false) {
		$subjects = $this
			->InstitutionSiteSectionClasses
			->find()
			->contain([
				'InstitutionSiteClasses'=>[
					'EducationSubjects',
					'Teachers.Genders'
				],
			])
			->where([
				'InstitutionSiteSectionClasses.institution_site_section_id' => $this->selectedSectionId,
				'InstitutionSiteSectionClasses.status' => 1
			])
			->toArray();
		if ($listOnly) {
			$subjectList = [];
			foreach ($subjects as $key => $value) {
				$subjectList[$value->institution_site_class->education_subject->id] = $value->institution_site_class->name;
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
	protected function getTeacherOptions() {
		
		$academicPeriodObj = $this->AcademicPeriods->get($this->_selectedAcademicPeriodId);
		$startDate = $this->AcademicPeriods->getDate($academicPeriodObj->start_date);
        $endDate = $this->AcademicPeriods->getDate($academicPeriodObj->end_date);

        $Staff = $this->Institutions->InstitutionSiteStaff;
		$query = $Staff->find('all')
						->find('withBelongsTo')
						->find('byInstitution', ['Institutions.id' => $this->institutionId])
						->find('byPositions', ['Institutions.id' => $this->institutionId, 'type' => 1]) // refer to OptionsTrait for type options
						->find('AcademicPeriod', ['academic_period_id'=>$academicPeriodObj->id])
						->where([
							$Staff->aliasField('institution_site_position_id') 
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
	 * Previously, the grades where populated based on a selected sectionId/classId.
	 * Those students who matched one of the grades will be included in the list.
	 *
	 * Since there will be more than one section where a subject could be linked to, the logic is changed to populate
	 * students using a longer route to obtain the grades for the current academic period.
	 * student_status_id = 1 is also included.
	 * @var integer
	 * @return array list of students
	 *
	 * @todo  modify the search to increase performance
	 */
	// protected function getStudentsOptions($entity, $studentIds) {
	protected function getStudentsOptions($entity) {
		// pr($entity);
		// from $entity, you can get the subject_id which you can use it to retrieve the list of grade_id from education_grades_subjects
		// from the list of grade_ids, you will use it to find the list of students from institution_site_section_students using grade_id and the section keys as conditions 
		$sectionKeys = [];
		foreach ($entity->institution_site_section_classes as $sectionClasses) {

			$sectionKeys[] = $sectionClasses->institution_site_section_id;
		}
		// $sections = $this->Institutions->InstitutionSiteSections
		// 	->find()
		// 	->contain(['InstitutionSiteSectionGrades'])
		// 	->where([
		// 		$this->Institutions->InstitutionSiteSections->aliasField('id') . ' IN ' => $sectionKeys,
		// 		$this->Institutions->InstitutionSiteSections->aliasField('academic_period_id') => $this->_selectedAcademicPeriodId,
		// 	])
		// 	->toArray();
		// $sectionGrades = [];
		// foreach ($sections as $sectionEntity) {
		// 	$sectionGradeObjects = $sectionEntity->institution_site_section_grades;
		// 	foreach ($sectionGradeObjects as $key=>$value) {
		// 		$sectionGrades[] = $value->education_grade_id;
		// 	}
		// }

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
		// pr($sectionKeys);
		// pr($entity->education_subject_id);
		// pr($grades);//die;

		$Students = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
		$query = $Students
			->find('all')
			->matching('Users')
			->where([
				$Students->aliasField('institution_site_section_id').' IN' => $sectionKeys,
				$Students->aliasField('education_grade_id').' IN' => $grades
			])
			->toArray();
		// pr($query);die;
		$studentOptions = ['-1' => $this->getMessage('Users.select_student'), '0' => $this->getMessage('Users.add_all_student')];
		
		foreach ($query as $student) {
			if ($student->has('_matchingData')) {
				$user = $student->_matchingData['Users'];
				$studentOptions[$user->id] = $user->name_with_id;
			} else {
				$this->log('Data corrupted with no security user for student: '. $student->id, 'debug');
			}
		}
		// pr($studentOptions);die;
		return $studentOptions;
	}

}
