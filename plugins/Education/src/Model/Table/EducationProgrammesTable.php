<?php
namespace Education\Model\Table;

use App\Model\Table\AppTable;

class EducationProgrammesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationCycles', ['className' => 'Education.EducationCycles']);
		$this->hasMany('EducationGrades', ['className' => 'Education.EducationGrades']);
	}
}
