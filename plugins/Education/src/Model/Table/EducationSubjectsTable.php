<?php
namespace Education\Model\Table;

use App\Model\Table\AppTable;

class EducationSubjectsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->addBehavior('Education.Setup');
		$this->hasMany('EducationGradesSubjects', ['className' => 'Education.EducationGradesSubjects']);
		$this->hasMany('InstitutionSiteClasses', ['className' => 'Institution.InstitutionSiteClasses']);
	}
}
