<?php
namespace AcademicPeriod\Model\Table;

use App\Model\Table\AppTable;

class AcademicPeriodsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('AcademicPeriodLevels', ['className' => 'AcademicPeriod.AcademicPeriodLevels']);
	}
}
