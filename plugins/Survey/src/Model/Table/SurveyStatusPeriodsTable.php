<?php
namespace Survey\Model\Table;

use App\Model\Table\AppTable;

class SurveyStatusPeriodsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('SurveyStatuses', ['className' => 'Survey.SurveyStatuses']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
	}
}
