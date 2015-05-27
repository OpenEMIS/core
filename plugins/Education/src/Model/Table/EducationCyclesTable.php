<?php
namespace Education\Model\Table;

use App\Model\Table\AppTable;

class EducationCyclesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationLevels', ['className' => 'Education.EducationLevels']);
		$this->hasMany('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);
	}
}
