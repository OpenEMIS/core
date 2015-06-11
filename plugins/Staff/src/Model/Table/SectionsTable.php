<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SectionsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_sections');
		parent::initialize($config);

		// 'Staff.Staff',
		// 'InstitutionSite',
		// 'AcademicPeriod',
		// 'EducationGrade'

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('InstitutionSites', ['className' => 'Institution.InstitutionSites']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

}
