<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteShiftsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('LocationInstitutionSites', ['className' => 'Institution.LocationInstitutionSites']);
	
		$this->hasMany('Sections', ['className' => 'Institution.InstitutionSiteSections', 'foreignKey' => 'institution_site_shift_id']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction() {
		if ($this->action == 'index') {
			// $this->fields['start_year']['visible'] = false;
			// $this->fields['end_year']['visible'] = false;
		}

		// pr($this->fields);die;
		$this->fields['academic_period_id']['type'] = 'select';
		$this->fields['location_institution_site_id']['type'] = 'select';
		
	}
}
