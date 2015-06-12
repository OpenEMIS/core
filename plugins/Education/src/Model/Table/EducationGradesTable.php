<?php
namespace Education\Model\Table;

use App\Model\Table\AppTable;

class EducationGradesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);

		// todo:mlee need to put in this association when it is created
		// $this->hasMany('EducationGradeSubject', ['className' => 'Education.EducationGradeSubject']);

	}
}
