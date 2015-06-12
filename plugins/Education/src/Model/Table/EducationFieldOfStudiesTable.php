<?php
namespace Education\Model\Table;

use App\Model\Table\AppTable;

class EducationFieldOfStudiesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationProgrammeOrientations', ['className' => 'Education.EducationProgrammeOrientations']);
		$this->hasMany('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);
	}
}
