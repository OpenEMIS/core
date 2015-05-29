<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class ShiftsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_shifts');
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('LocationInstitutionSites', ['className' => 'Institution.LocationInstitutionSites']);
	
		$this->hasMany('Sections', ['className' => 'Institution.Sections', 'foreignKey' => 'institution_site_shift_id']);
	}

	public function validationDefault(Validator $validator) {
		$validator->add('name', 'notBlank', [
			'rule' => 'notBlank'
		]);
		return $validator;
	}

	public function beforeAction() {
		
	}
}
