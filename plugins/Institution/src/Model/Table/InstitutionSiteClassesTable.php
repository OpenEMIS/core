<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteClassesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
		
		$this->Institutions->hasMany('InstitutionSiteClassStaff', ['className' => 'Institution.InstitutionSiteClassStaff']);
		$this->Institutions->hasMany('InstitutionSiteClassStudents', ['className' => 'Institution.InstitutionSiteClassStudents']);
		$this->Institutions->hasMany('InstitutionSiteSectionClasses', ['className' => 'Institution.InstitutionSiteSectionClasses']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction() {
		
	}
}
