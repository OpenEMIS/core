<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteSectionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		// $this->belongsTo('Staff', ['className' => 'Staff.Staff', 'foreignKey' => 'staff_id']);
		$this->belongsTo('InstitutionSiteShifts', ['className' => 'Institution.InstitutionSiteShifts']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
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
