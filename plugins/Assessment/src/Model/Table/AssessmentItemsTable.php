<?php
namespace Assessment\Model\Table;

use App\Model\Table\AppTable;

class AssessmentItemsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('AssessmentItemTypes', ['className' => 'Assessment.AssessmentItemTypes']);
		$this->hasMany('AssessmentItemResults', ['className' => 'Assessment.AssessmentItemResults']);
	}
}
