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

		$this->fields['academic_period_id']['type'] = 'readonly';
		$this->fields['education_subject_id']['type'] = 'readonly';
		
		// $this->ControllerAction->addField('education_level', ['type' => 'select', 'onChangeReload' => true]);
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
    	$this->fields['academic_period_id']['visible'] = false;

    	$this->fields['education_subject_id']['order'] = 3;
		$this->ControllerAction->addField('teachers', [
			'type' => 'string', 
			'order' => 4,
		]);
		$this->ControllerAction->addField('male_students', [
			'type' => 'integer', 
			'order' => 5,
		]);
		$this->ControllerAction->addField('female_students', [
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
** edit action methods
**
******************************************************************************************************************/
	public function editBeforeAction(Event $event) {
		// $this->fields['education_level']['type'] = 'disabled';
	}

	public function editBeforeQuery(Event $event, Query $query, $contain) {
		// $contain[] = 'EducationProgrammes';
		return compact('query', 'contain');
	}



/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
	public function addBeforeAction($event) {
	}


/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
    public function viewBeforeAction($event) {
		// $query = $this->request->query;
 		
  //   	if (array_key_exists('academic_period', $query) || array_key_exists('grade', $query)) {
  //   		if (array_key_exists('academic_period', $query)) {
	 //    		unset($this->ControllerAction->buttons['view']['url']['academic_period']);
  //   		}
  //   		if (array_key_exists('grade', $query)) {
	 //    		unset($this->ControllerAction->buttons['view']['url']['grade']);
  //   		}
  //   		$action = $this->ControllerAction->buttons['view']['url'];
		// 	$this->controller->redirect($action);
  //   	}

		$this->fields['no_of_seats']['visible'] = false;
		$this->fields['modified_user_id']['visible'] = false;
		$this->fields['modified']['visible'] = false;
		$this->fields['created_user_id']['visible'] = false;
		$this->fields['created']['visible'] = false;

		$this->fields['academic_period_id']['order'] = 1;

		// $this->ControllerAction->addField('section_name', [
		// 	'type' => 'element',
		// 	'element' => 'Institution.Sections/multi_grade',
		// 	'data' => [	
		// 		'grades'=>[]
		// 	]
		// ]);
		$this->ControllerAction->addField('section_name', ['type' => 'string', 'order' => 2]);

		$this->fields['name']['order'] = 3;
		$this->ControllerAction->addField('education_subject_code', ['type' => 'string', 'order' => 4]);
		$this->fields['education_subject_id']['order'] = 5;

		$this->ControllerAction->addField('teachers', [
			'label' => '',
			'type' => 'element',
			'order' => 6,
			'element' => 'Institution.Classes/teachers',
			'data' => [	
				'teachers'=>[],
				'teacherOptions'=>[]
			]
		]);

		$this->ControllerAction->addField('students', [
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

}
