<?php
namespace Education\Model\Table;

use App\Model\Table\AppTable;

class EducationGradesSubjectsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
	}
}
