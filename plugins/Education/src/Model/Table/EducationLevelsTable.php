<?php
namespace Education\Model\Table;

use App\Model\Table\AppTable;

class EducationLevelsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationSystems', ['className' => 'Education.EducationSystems']);
		$this->hasMany('EducationCycles', ['className' => 'Education.EducationCycles']);
	}
}
