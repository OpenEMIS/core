<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SectionsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_sections');
		parent::initialize($config);
		
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		// $this->belongsTo('Staff', ['className' => 'Security.SecurityUsers', 'foreignKey' => 'staff_id']);
		$this->belongsTo('InstitutionSiteShifts', ['className' => 'Institution.Shifts']);
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
