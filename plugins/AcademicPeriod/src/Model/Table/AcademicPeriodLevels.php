<?php
namespace AcademicPeriod\Model\Table;

use App\Model\Table\AppTable;

class AcademicPeriodLevelsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
	}
}
