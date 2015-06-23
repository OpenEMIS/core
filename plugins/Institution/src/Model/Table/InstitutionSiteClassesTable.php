<?php
namespace Institution\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteClassesTable extends AppTable {
	private $_selectedSection = 0;
	private $_selectedAcademicPeriod = 0;

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
		// $this->EducationGrades = $this->EducationProgrammes->EducationGrades;
		// $this->AcademicPeriods = $this->Institutions->InstitutionSiteShifts->AcademicPeriods;
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction($event) {

		$this->ControllerAction->field('section_name', ['type' => 'string', 'order' => 2]);
		// $this->fields['academic_period_id']['type'] = 'readonly';
		// $this->fields['education_subject_id']['type'] = 'readonly';
		
		// $this->ControllerAction->field('education_level', ['type' => 'select', 'onChangeReload' => true]);
		// $this->EducationLevels = TableRegistry::get('Education.EducationLevels');

		// $this->fields['academic_period_id']['order'] = 0;
	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
    public function indexBeforeAction($event) { 
		$query = $this->request->query;
 		
 		$institutionsId = $this->Session->read('Institutions.id');
		$conditions = array(
			'InstitutionSiteProgrammes.institution_site_id' => $institutionsId
		);
		$academicPeriodOptions = $this->Institutions->InstitutionSiteProgrammes->getAcademicPeriodOptions($conditions);
		if (empty($academicPeriodOptions)) {
			$this->Alert->warning('Institutions.noProgrammes');
		}
		$this->_selectedAcademicPeriod = isset($query['academic_period']) ? $query['academic_period'] : key($academicPeriodOptions);
		$this->_selectedAcademicPeriod = $this->checkIdInOptions($this->_selectedAcademicPeriod, $academicPeriodOptions);

		$sectionOptions = $this->InstitutionSiteSections
								->find('list')
								->where([
									'academic_period_id'=>$this->_selectedAcademicPeriod, 
									'institution_site_id'=>$institutionsId
								])
								->toArray();
		if (empty($sectionOptions)) {
			$this->Alert->warning('Institutions.noSections');
		}
		$this->_selectedSection = isset($query['section']) ? $query['section'] : key($sectionOptions);
		$this->_selectedSection = $this->checkIdInOptions($this->_selectedSection, $sectionOptions);

		$toolbarElements = [
            ['name' => 'Institution.Classes/controls', 
             'data' => [
	            	'academicPeriodOptions'=>$academicPeriodOptions, 
	            	'selectedAcademicPeriod'=>$this->_selectedAcademicPeriod, 
	            	'sectionOptions'=>$sectionOptions, 
	            	'selectedSection'=>$this->_selectedSection, 
	            ],
	         'options' => []
            ]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);

		$this->fields['no_of_seats']['visible'] = false;
		$this->fields['section_name']['visible'] = false;
    	$this->fields['academic_period_id']['visible'] = false;

    	$this->fields['education_subject_id']['order'] = 3;
		$this->ControllerAction->field('teachers', [
			'type' => 'string', 
			'order' => 4,
		]);
		$this->ControllerAction->field('male_students', [
			'type' => 'integer', 
			'order' => 5,
		]);
		$this->ControllerAction->field('female_students', [
			'type' => 'integer', 
			'order' => 6,
		]);

	}

    public function findBySections(Query $query, array $options) {
    	return $query
			->join([
				[
					'table' => 'institution_site_section_classes',
					'alias' => 'InstitutionSiteSectionClass',
					'conditions' => [
						'InstitutionSiteSectionClass.institution_site_class_id = InstitutionSiteClasses.id',
						'InstitutionSiteSectionClass.institution_site_section_id' => $this->_selectedSection
					]
				]
			]);
    }

	public function indexBeforePaginate($event, $model, $paginateOptions) {
		$paginateOptions['finder'] = ['bySections' => []];
		$paginateOptions['conditions'][]['academic_period_id'] = $this->_selectedAcademicPeriod;
		return $paginateOptions;
	}

	public function indexAfterAction(Event $event, $data) {
	// 	foreach ($data as $key => $value) {
	// 		if(!empty($value['InstitutionSiteClass']['id'])) {
	// 			$data[$key]['InstitutionSiteClass']['gender'] = $this->InstitutionSiteClassStudent->getGenderTotalByClass($value['InstitutionSiteClass']['id']);
	// 		}else{
	// 			unset($data[$key]);
	// 		}
	// 	}

		return $data;
	}


/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
	public function addBeforeAction($event) {
		$query = $this->request->query;
    	if (array_key_exists('academic_period', $query) || array_key_exists('section', $query)) {
    		if (array_key_exists('academic_period', $query)) {
	    		unset($this->ControllerAction->buttons['add']['url']['academic_period']);
    		}
    		if (array_key_exists('section', $query)) {
	    		unset($this->ControllerAction->buttons['add']['url']['section']);
    		}
    		$action = $this->ControllerAction->buttons['add']['url'];
			$this->controller->redirect($action);
    	}
    	if (array_key_exists($this->alias(), $this->request->data)) {
	    	$_data = $this->request->data[$this->alias()];
			$this->_selectedAcademicPeriod = $_data['academic_period_id'];
		}

		$institutionsId = $this->Session->read('Institutions.id');

		$this->fields['name']['visible'] = false;
		$this->fields['no_of_seats']['visible'] = false;
		$this->fields['education_subject_id']['visible'] = false;


		/**
		 * academic_period_id field setup
		 */
		$academicPeriodOptions = $this->getAcademicPeriodOptions();
    	$this->fields['academic_period_id']['order'] = 1;
		$this->fields['academic_period_id']['type'] = 'select';
		$this->fields['academic_period_id']['options'] = $academicPeriodOptions;
		$this->fields['academic_period_id']['onChangeReload'] = true;
	

		$sectionOptions = $this->InstitutionSiteSections
								->find('list')
								->where([
									'academic_period_id'=>$this->_selectedAcademicPeriod, 
									'institution_site_id'=>$institutionsId
								])
								->toArray();
		if (empty($sectionOptions)) {
			$this->Alert->warning('Institutions.noSections');
		}
		$this->_selectedSection = isset($query['section']) ? $query['section'] : key($sectionOptions);
		$this->_selectedSection = $this->checkIdInOptions($this->_selectedSection, $sectionOptions);

		$this->fields['section_name'] = [
			'type' => 'select',
			'visible' => true,
			'order' => 2,
			'options' => $sectionOptions,
			'onChangeReload' => true
		];

		$this->ControllerAction->field('classes', [
			'label' => '',
			'type' => 'element',
			'order' => 6,
			'element' => 'Institution.Classes/classes',
			'data' => [	
				'subjects'=>[],
				'teachers'=>[]
			]
		]);

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
		$this->fields['classes']['data'] = [
			'teachers' => $teachers,
			'subjects' => $subjects,
		];

		return $entity;
	}


/******************************************************************************************************************
**
** edit action methods
**
******************************************************************************************************************/
	public function editBeforeAction(Event $event) {
		$this->fields['name']['order'] = 1;
		$this->fields['no_of_seats']['order'] = 2;
		$this->fields['academic_period_id']['order'] = 3;
		$this->fields['education_subject_id']['order'] = 4;

		$this->ControllerAction->field('teachers', [
			'label' => '',
			'type' => 'element',
			'order' => 5,
			'element' => 'Institution.Classes/teachers',
			'data' => [	
				'teachers'=>[],
				'teacherOptions'=>[]
			]
		]);

		$this->ControllerAction->field('students', [
			'label' => '',
			'type' => 'element',
			'order' => 6,
			'element' => 'Institution.Classes/students',
			'data' => [	
				'students'=>[],
				'studentOptions'=>[],
				'categoryOptions'=>[]
			]
		]);
	}

	public function editBeforeQuery(Event $event, Query $query, $contain) {
		// $this->hasMany('InstitutionSiteSectionClasses', ['className' => 'Institution.InstitutionSiteSectionClasses']);
		// $this->hasMany('InstitutionSiteClassStudents', 	['className' => 'Institution.InstitutionSiteClassStudents']);
		// $this->hasMany('InstitutionSiteClassStaff', 	['className' => 'Institution.InstitutionSiteClassStaff']);

		$contain = array_merge([
			'AcademicPeriods', 
			'EducationSubjects',
			'InstitutionSiteClassStaff'=>['Users'],
			'InstitutionSiteClassStudents'=>['Users'=>['Genders']],
			'InstitutionSiteSectionClasses'
		], $contain);
		// pr($contain);
		return compact('query', 'contain');
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->_selectedSection = $entity->institution_site_section_classes[0]->institution_site_section_id;
		$query = $this
			->Institutions
			->InstitutionSiteStaff
			->find()
			->contain(['Users'])
			->where(['InstitutionSiteStaff.institution_site_id'=>$entity->institution_site_id])
			// ->where(['InstitutionSiteStaff.status'=>1])
			->toArray();
		$teachers = [__('Add Teachers')];
		foreach ($query as $teacher) {
			$teachers[$teacher->user->id] = $teacher->user->name_with_id;
		}
		$query = $this
			->Institutions
			->InstitutionSiteStudents
			->find()
			->contain(['Users'])
			->where(['InstitutionSiteStudents.institution_site_id'=>$entity->institution_site_id])
			// ->where(['InstitutionSiteStaff.status'=>1])
			->toArray();
		$students = [__('Add Students')];
		foreach ($query as $student) {
			$students[$student->user->id] = $student->user->name_with_id;
		}

		$this->fields['teachers']['data']['teachers'] = $entity->institution_site_class_staff;
		$this->fields['teachers']['data']['teacherOptions'] = $teachers;
		$this->fields['students']['data']['students'] = $entity->institution_site_class_students;
		$this->fields['students']['data']['studentOptions'] = $students;
	

		$this->fields['section_name']['visible'] = false;
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
** view action methods
**
******************************************************************************************************************/
    public function viewBeforeAction($event) {

		$this->fields['no_of_seats']['visible'] = false;
		$this->fields['modified_user_id']['visible'] = false;
		$this->fields['modified']['visible'] = false;
		$this->fields['created_user_id']['visible'] = false;
		$this->fields['created']['visible'] = false;

		$this->fields['academic_period_id']['order'] = 1;

		$this->ControllerAction->field('section_name', ['type' => 'string', 'order' => 2]);

		$this->fields['name']['order'] = 3;
		$this->ControllerAction->field('education_subject_code', ['type' => 'string', 'order' => 4]);
		$this->fields['education_subject_id']['order'] = 5;

		$this->ControllerAction->field('teachers', [
			'label' => '',
			'type' => 'element',
			'order' => 6,
			'element' => 'Institution.Classes/teachers',
			'data' => [	
				'teachers'=>[],
				'teacherOptions'=>[]
			]
		]);

		$this->ControllerAction->field('students', [
			'label' => '',
			'type' => 'element',
			'order' => 7,
			'element' => 'Institution.Classes/students',
			'data' => [	
				'students'=>[],
				'studentOptions'=>[],
				'categoryOptions'=>[]
			]
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
** essential functions
**
******************************************************************************************************************/

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
			if ($this->_selectedAcademicPeriod != 0) {
				if (!array_key_exists($this->_selectedAcademicPeriod, $list)) {
					$this->_selectedAcademicPeriod = key($list);
				}
			} else {
				$this->_selectedAcademicPeriod = key($list);
			}
		}
		return $list;

	}
	
	private function getSubjectOptions($listOnly=false) {
		$Grade = $this->InstitutionSiteSectionClasses->InstitutionSiteSections->InstitutionSiteSectionGrades;
		$gradeOptions = $Grade->find()
							->contain('EducationGrades')
							->where([
								$Grade->aliasField('institution_site_section_id') => $this->_selectedSection,
								$Grade->aliasField('status') => 1
							])
							->toArray();
		$gradeData = [];
		foreach ($gradeOptions as $key => $value) {
			$gradeData[$value->education_grade->id] = $value->education_grade->name;
		}
		// pr($gradeData);
		// pr(array_keys($gradeData));
		$subjects = $this
				->EducationSubjects
				->EducationGradesSubjects
				->find()
				->contain(['EducationSubjects'])
				->where([
					'EducationGradesSubjects.education_grade_id IN' => array_keys($gradeData),
					'EducationGradesSubjects.visible' => 1
				])
				// ->__toString();
				->toArray();
		// pr($subjects);die;
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

}
