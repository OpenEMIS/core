<?php
namespace Education\Model\Table;

use App\Model\Table\ControllerActionTable;

class EducationGradesSubjectsTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
	}
}
