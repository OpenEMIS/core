<?php
namespace Institution\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteClassesTable extends AppTable {
	private $_selectedSectionId = 0;
	private $_selectedAcademicPeriodId = 0;

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', 			['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', 				['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('EducationSubjects', 			['className' => 'Education.EducationSubjects']);
		
		$this->hasMany('InstitutionSiteSectionClasses', ['className' => 'Institution.InstitutionSiteSectionClasses']);
		$this->hasMany('InstitutionSiteClassStudents', 	['className' => 'Institution.InstitutionSiteClassStudents']);
		$this->hasMany('InstitutionSiteClassStaff', 	['className' => 'Institution.InstitutionSiteClassStaff']);

		// $this->belongsToMany('InstitutionSiteSections', ['through' => 'InstitutionSiteSectionClasses']);

		/**
		 * Short cuts to initialised models set in relations.
		 * By using initialised models set in relations, the relation's className is set at a single place.
		 * In add operations, these models attributes are empty by default.
		 */
		$this->InstitutionSiteSections = $this->Institutions->InstitutionSiteSections;
		$this->InstitutionSiteProgrammes = $this->Institutions->InstitutionSiteProgrammes;
		$this->InstitutionSiteSectionGrades = $this->InstitutionSiteSectionClasses->InstitutionSiteSections->InstitutionSiteSectionGrades;
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction($event) {

    	$this->ControllerAction->field('academic_period_id', ['type' => 'select', 'visible' => ['view'=>true, 'edit'=>true, 'add'=>true], 'onChangeReload' => true]);
    	$this->ControllerAction->field('created', ['type' => 'string', 'visible' => false]);
    	$this->ControllerAction->field('created_user_id', ['type' => 'string', 'visible' => false]);
		$this->ControllerAction->field('education_subject_code', ['type' => 'string', 'visible' => ['view'=>true]]);
		$this->ControllerAction->field('education_subject_id', ['type' => 'string', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('modified', ['type' => 'string', 'visible' => false]);
    	$this->ControllerAction->field('modified_user_id', ['type' => 'string', 'visible' => false]);
    	$this->ControllerAction->field('name', ['type' => 'string', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
		$this->ControllerAction->field('no_of_seats', ['type' => 'integer', 'attr'=>['min' => 1], 'visible' => false]);
		$this->ControllerAction->field('section_name', ['type' => 'select', 'visible' => ['view'=>true], 'onChangeReload' => true]);

		$this->ControllerAction->field('students', [
			'label' => '',
			'override' => true,
			'type' => 'element',
			'element' => 'Institution.Classes/students',
			'data' => [	
				'students'=>[],
				'studentOptions'=>[],
				'categoryOptions'=>[]
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
			'label' => '',
			'override' => true,
			'type' => 'element',
			'element' => 'Institution.Classes/teachers',
			'data' => [	
				'teachers'=>[],
				'teacherOptions'=>[]
			],
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
			'name', 'education_subject_id', 'teachers',
			'male_students', 'female_students',
		]);

	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
    public function indexBeforeAction($event) {
		$this->fields['teachers']['type'] = 'string';

		$Classes = $this;
		$Sections = $this->InstitutionSiteSections;
 		$institutionsId = $this->Session->read('Institutions.id');

		$academicPeriodOptions = $this->getAcademicPeriodOptions();
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

		$sectionOptions = $Sections->find('list')
									->where([
										'academic_period_id'=>$this->_selectedAcademicPeriodId, 
										'institution_site_id'=>$institutionsId
									])
									->toArray();
		$selectedAcademicPeriodId = $this->_selectedAcademicPeriodId;
		if (empty($sectionOptions)) {
			$this->Alert->warning('Institutions.noSections');
		}
		$this->_selectedSectionId = $this->queryString('section_id', $sectionOptions);
		$this->advancedSelectOptions($sectionOptions, $this->_selectedSectionId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
			'callable' => function($id) use ($Classes, $institutionsId, $selectedAcademicPeriodId) {
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
										$Classes->aliasField('institution_site_id') => $institutionsId,
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
	            	'selectedSection'=>$this->_selectedSectionId, 
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
						'InstitutionSiteSectionClass.institution_site_section_id' => $this->_selectedSectionId
					]
				]
			]);
    }

	public function indexBeforePaginate($event, $request, $paginateOptions) {
		$paginateOptions['finder'] = ['bySections' => []];
		$paginateOptions['conditions'][]['academic_period_id'] = $this->_selectedAcademicPeriodId;
		return $paginateOptions;
	}


/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
    public function viewBeforeAction($event) {
		$this->ControllerAction->setFieldOrder([
			'academic_period_id', 'section_name',
			'name', 'education_subject_code', 'education_subject_id', 
			'teachers', 'students',
		]);
	}

	public function viewBeforeQuery(Event $event, Query $query, $contain) {
		$contain = array_merge([
			'InstitutionSiteSectionClasses' => ['InstitutionSiteSections']
		], $contain);
		return compact('query', 'contain');
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$sections = [];
		foreach ($entity->institution_site_section_classes as $key => $value) {
			$sections[] = $value->institution_site_section->name;
		}
		$entity->section_name = implode(', ', $sections);
		
		$this->fields['teachers']['data']['teachers'] = $this
			->InstitutionSiteClassStaff
			->find()
			->contain(['Users'])
			->where(['InstitutionSiteClassStaff.institution_site_class_id'=>$entity->id])
			->toArray();

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
	public function addBeforeAction($event) {
		$this->fields['name']['visible'] = false;
		$this->fields['teachers']['visible'] = false;
		$this->fields['students']['visible'] = false;
		$this->fields['education_subject_id']['visible'] = false;

		$this->fields['section_name']['visible'] = true;
		$this->fields['classes']['visible'] = true;
		$this->ControllerAction->setFieldOrder([
			'academic_period_id', 'section_name', 'classes',
		]);

		$Sections = $this->InstitutionSiteSections;
 		$institutionsId = $this->Session->read('Institutions.id');

		$academicPeriodOptions = $this->getAcademicPeriodOptions();
		$this->_selectedAcademicPeriodId = $this->postString('academic_period_id', $academicPeriodOptions);
		$this->advancedSelectOptions($academicPeriodOptions, $this->_selectedAcademicPeriodId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noSections')),
			'callable' => function($id) use ($Sections, $institutionsId) {
				return $Sections->findByInstitutionSiteIdAndAcademicPeriodId($institutionsId, $id)->count();
			}
		]);

		$sectionOptions = $Sections->find('list')
									->where([
										'academic_period_id'=>$this->_selectedAcademicPeriodId, 
										'institution_site_id'=>$institutionsId
									])
									->toArray();
		$this->_selectedSectionId = $this->postString('section_name', $sectionOptions);
		$this->advancedSelectOptions($sectionOptions, $this->_selectedSectionId);
		
		$this->fields['academic_period_id']['options'] = $academicPeriodOptions;
		$this->fields['section_name']['options'] = $sectionOptions;

	}

	public function addBeforePatch($event, $entity, $data, $options) {
		$commonData = $data['InstitutionSiteClasses'];
		$data['InstitutionSiteClasses'] = [];
		foreach ($data['MultiClasses'] as $key=>$class) {
			if (isset($class['education_subject_id']) && isset($class['institution_site_class_staff'])) {
				$class['academic_period_id'] = $commonData['academic_period_id'];
				$class['institution_site_id'] = $commonData['institution_site_id'];
				$class['institution_site_section_classes'] = [
					[
						'status' => 1,
						'institution_site_section_id' => $commonData['section_name']
					]
				];
				$data['InstitutionSiteClasses'][] = $class;
			}
		}
		unset($data['MultiClasses']);
		$class = null;

		$classes = $this->newEntities($data['InstitutionSiteClasses']);
		$error = false;
		foreach ($classes as $class) {
		    if ($class->errors()) {
		    	$error = $class->errors();
		    }
		}
		if (!$error) {
			foreach ($classes as $class) {
		    	$this->save($class);
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
	}

	public function addAfterAction(Event $event, Entity $entity) {
		$institutionsId = $this->Session->read('Institutions.id');
		$query = $this
				->Institutions
				->InstitutionSiteStaff
				->find()
				->contain(['Users'])
				->where(['InstitutionSiteStaff.institution_site_id'=>$institutionsId])
				->toArray();
		$teachers = [];
		foreach ($query as $key => $value) {
			$teachers[$value->user->id] = $value->user->name;
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
		$this->ControllerAction->setFieldOrder([
			'name', 'no_of_seats', 
			'academic_period_id', 'education_subject_id', 
			'teachers', 'students',
		]);
	}

	public function editBeforeQuery(Event $event, Query $query, $contain) {
		$contain = array_merge([
			'AcademicPeriods', 
			'EducationSubjects',
			'InstitutionSiteClassStaff'=>['Users'],
			'InstitutionSiteClassStudents'=>['Users'=>['Genders']],
			'InstitutionSiteSectionClasses'
		], $contain);
		return compact('query', 'contain');
	}

	public function editBeforePatch($event, $entity, $data, $options) {
		/**
		 * Unable to utilise updateAll for this scenario.
		 * Only new student records will be saved as status=1 at the later part of this scope.
		 * Existitng records which is not removed from the UI list, will remain as status=0 instead of 1.
		 */
		// $this->InstitutionSiteClassStudents->updateAll(['status'=>0], ['institution_site_class_id' => $entity->id]);
		// $this->InstitutionSiteClassStaff->updateAll(['status'=>0], ['institution_site_class_id' => $entity->id]);


		/**
		 * In students.ctp && teachers.ctp, we set the security_user_id as the array keys for easy search and compare.
		 * Assign back original record's id to the new list so as to preserve id numbers.
		 */
		foreach($entity->institution_site_class_students as $key => $record) {
			$k = $record->security_user_id;
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
		foreach($entity->institution_site_class_staff as $key => $record) {
			$k = $record->security_user_id;
			if (array_key_exists('institution_site_class_staff', $data[$this->alias()])) {
				if (!array_key_exists($k, $data[$this->alias()]['institution_site_class_staff'])) {			
					$data[$this->alias()]['institution_site_class_staff'][$k] = [
						'id' => $record->id,
						'status' => 0 
					];
				} else {
					$data[$this->alias()]['institution_site_class_staff'][$k]['id'] = $record->id;
				}
			} else {
				$data[$this->alias()]['institution_site_class_staff'][$k] = [
					'id' => $record->id,
					'status' => 0 
				];
			}
		}		
		return compact('entity', 'data', 'options');
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->_selectedSectionId = $entity->institution_site_section_classes[0]->institution_site_section_id;

		/**
		 * @todo should have additional filter; by start_date, end_date, staff_type_id, staff_status_id & institution_site_position_id
		 */
		$query = $this
			->Institutions
			->InstitutionSiteStaff
			->find()
			->contain(['Users'])
			->where([
				'InstitutionSiteStaff.institution_site_id'=>$entity->institution_site_id,
			])
			->toArray();
		$teacherOptions = [__('Select Teacher')];
		foreach ($query as $teacher) {
			$teacherOptions[$teacher->user->id] = $teacher->user->name_with_id;
		}
		$teachers = $entity->institution_site_class_staff;
		/**
		 * Check if the request is a page reload
		 */
		if (count($this->request->data)>0 && $this->request->data['submit']=='add') {
			$teachers = [];
			/**
			 * Populate records in the UI table & unset the record from teacherOptions
			 */
			if (array_key_exists('institution_site_class_staff', $this->request->data[$this->alias()])) {
				foreach ($this->request->data[$this->alias()]['institution_site_class_staff'] as $row) {
					if ($row['status']>0 && array_key_exists($row['security_user_id'], $teacherOptions)) {
						$id = $row['security_user_id'];
						$teachers[] = $this->createVirtualEntity($id, $entity, 'staff');
						unset($teacherOptions[$id]);
					}
				}
			}
			/**
			 * Insert the newly record into the UI table & unset the record from teacherOptions
			 */
			if (array_key_exists('staff_id', $this->request->data) && $this->request->data['staff_id']!=0) {
				$id = $this->request->data['staff_id'];
				$teachers[] = $this->createVirtualEntity($id, $entity, 'staff');
				unset($teacherOptions[$id]);
			}
		} else {
			/**
			 * Just unset the record from teacherOptions on first page load
			 */
			foreach ($entity->institution_site_class_staff as $row) {
				if ($row->status>0 && array_key_exists($row->security_user_id, $teacherOptions)) {
					unset($teacherOptions[$row->security_user_id]);
				}
			}
		}


		/**
		 * @todo should have additional filter; by start_date, end_date & education_programme_id
		 */
		$query = $this
			->Institutions
			->InstitutionSiteStudents
			->find()
			->contain(['Users'])
			->where([
				'InstitutionSiteStudents.institution_site_id'=>$entity->institution_site_id
			])
			->toArray();
		$studentOptions = [__('Select Student')];
		foreach ($query as $student) {
			$studentOptions[$student->user->id] = $student->user->name_with_id;
		}
		$students = $entity->institution_site_class_students;
		/**
		 * Check if the request is a page reload
		 */
		if (count($this->request->data)>0 && $this->request->data['submit']=='add') {
			$students = [];
			/**
			 * Populate records in the UI table & unset the record from studentOptions
			 */
			if (array_key_exists('institution_site_class_students', $this->request->data[$this->alias()])) {
				foreach ($this->request->data[$this->alias()]['institution_site_class_students'] as $row) {
					if ($row['status']>0 && array_key_exists($row['security_user_id'], $studentOptions)) {
						$id = $row['security_user_id'];
						$students[] = $this->createVirtualEntity($id, $entity, 'students');
						unset($studentOptions[$id]);
					}
				}
			}
			/**
			 * Insert the newly record into the UI table & unset the record from studentOptions
			 */
			if (array_key_exists('student_id', $this->request->data) && $this->request->data['student_id']!=0) {
				$id = $this->request->data['student_id'];
				$students[] = $this->createVirtualEntity($id, $entity, 'students');
				unset($studentOptions[$id]);
			}
		} else {
			/**
			 * Just unset the record from studentOptions on first page load
			 */
			foreach ($entity->institution_site_class_students as $row) {
				if ($row->status>0 && array_key_exists($row->security_user_id, $studentOptions)) {
					unset($studentOptions[$row->security_user_id]);
				}
			}
		}

		$this->fields['teachers']['data'] = [
			'teachers' => $teachers,
			'teacherOptions' => $teacherOptions
		];
		$this->fields['students']['data'] = [
			'students' => $students,
			'studentOptions' => $studentOptions
		];
	
		$this->fields['academic_period_id']['type'] = 'readonly';
		$this->fields['academic_period_id']['attr']['value'] = $this->getAcademicPeriodOptions()[$entity->academic_period_id];
		
		$subjects = $this->getSubjectOptions(true);
		$this->fields['education_subject_id']['type'] = 'readonly';
		if (array_key_exists($entity->education_subject_id, $subjects)) {
			$this->fields['education_subject_id']['attr']['value'] = $subjects[$entity->education_subject_id]->name;
		} else {
			$this->fields['education_subject_id']['attr']['value'] = $this->EducationSubjects->get($entity->education_subject_id)->name;
		}
	
		return $entity;
	}


/******************************************************************************************************************
**
** essential functions
**
******************************************************************************************************************/
	protected function createVirtualEntity($id, $entity, $persona) {
		$model = 'InstitutionSite'.ucwords(strtolower($persona));
		$userData = $this->Institutions->$model->find()->contain(['Users'=>['Genders']])->where(['security_user_id'=>$id])->first();
		$data = [
			'id'=>$this->getExistingRecordId($id, $entity, $persona),
			'security_user_id'=>$id,
			'institution_site_class_id'=>$entity->id,
			'institution_site_section_id'=>$entity->toArray()['institution_site_section_classes'][0]['institution_site_section_id'],
			'status'=>1,
			'user'=>[]
		];
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
			if ($data->security_user_id == $id) {
				$recordId = $data->id;
			}
		}
		return $recordId;
	}

	private function getAcademicPeriodOptions() {
		$institutionsId = $this->Session->read('Institutions.id');
		$conditions = array(
			'InstitutionSiteProgrammes.institution_site_id' => $institutionsId
		);
		$list = $this->Institutions->InstitutionSiteProgrammes->getAcademicPeriodOptions($conditions);
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
	
	private function getSubjectOptions($listOnly=false) {
		$Grade = $this->InstitutionSiteSectionGrades;
		$gradeOptions = $Grade->find()
							->contain('EducationGrades')
							->where([
								$Grade->aliasField('institution_site_section_id') => $this->_selectedSectionId,
								$Grade->aliasField('status') => 1
							])
							->toArray();
		$gradeData = [];
		foreach ($gradeOptions as $key => $value) {
			$gradeData[$value->education_grade->id] = $value->education_grade->name;
		}
		$subjects = $this
				->EducationSubjects
				->EducationGradesSubjects
				->find()
				->contain(['EducationSubjects'])
				->where([
					'EducationGradesSubjects.education_grade_id IN' => array_keys($gradeData),
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
		return $data;
	}

	private function getExistedSubjects($listOnly=false) {
		$subjects = $this
			->InstitutionSiteSectionClasses
			->find()
			->contain([
				'InstitutionSiteClasses'=>[
					'EducationSubjects',
					'InstitutionSiteClassStaff'=>['Users'=>['Genders']]
				],
			])
			->where([
				'InstitutionSiteSectionClasses.institution_site_section_id' => $this->_selectedSectionId,
				'InstitutionSiteSectionClasses.status' => 1
			])
			->toArray();
		if ($listOnly) {
			$subjectList = [];
			foreach ($subjects as $key => $value) {
				$subjectList[$value->institution_site_class->education_subject->id] = $value->institution_site_class->education_subject->name;
			}
			$data = $subjectList;
		} else {
			$data = $subjects;
		}
		return $data;
	}

}
