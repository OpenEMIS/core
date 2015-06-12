<?php
namespace Education\Model\Table;

use App\Model\Table\AppTable;

class EducationProgrammeOrientationsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('EducationFieldOfStudies', ['className' => 'Education.EducationFieldOfStudies']);
	}
}
