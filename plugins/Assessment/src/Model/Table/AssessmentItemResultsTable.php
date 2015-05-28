<?php
namespace Assessment\Model\Table;

use App\Model\Table\AppTable;

class AssessmentItemResultsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('AssessmentItems', ['className' => 'Assessment.AssessmentItems']);
		$this->belongsTo('AssessmentResults', ['className' => 'Assessment.AssessmentResults']);
	}
}
