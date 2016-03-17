<?php
namespace Assessment\Model\Table;

use App\Model\Table\AppTable;

class AssessmentPeriodsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
	}
}
