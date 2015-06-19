<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
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

}
