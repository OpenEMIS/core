<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteShiftsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', 		['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', 			['className' => 'Institution.Institutions', 			'foreignKey' => 'institution_site_id']);
		$this->belongsTo('LocationInstitutionSites',['className' => 'Institution.LocationInstitutionSites']);
	
		$this->hasMany('Sections', 					['className' => 'Institution.InstitutionSiteSections', 	'foreignKey' => 'institution_site_shift_id']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

	public function beforeAction() {

		$this->fields['name']['type'] = 'string';
		$this->fields['academic_period_id']['type'] = 'select';		
		$this->fields['start_time']['type'] = 'string';
		$this->fields['end_time']['type'] = 'string';


		$this->fields['name']['order'] = 0;
		$this->fields['academic_period_id']['order'] = 1;		
		$this->fields['start_time']['order'] = 2;
		$this->fields['end_time']['order'] = 3;
		$this->fields['location_institution_site_id']['order'] = 4;

	}

	public function addEditBeforeAction($event) {

		$this->fields['location_institution_site_id']['visible'] = false;

	}

	public function onPopulateSelectOptions(Event $event, $query) {
		$query = parent::onPopulateSelectOptions($event, $query);
		$query->
		// pr($result->toArray());
		return $query;
	}

}
