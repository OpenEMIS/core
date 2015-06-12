<?php
namespace Education\Model\Table;

use App\Model\Table\AppTable;

class EducationSubjectsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('StudentClasses', ['className' => 'Student.StudentClasses']);
		$this->hasMany('EducationGradeSubject', ['className' => 'Education.EducationGradeSubject']);
	}
}
