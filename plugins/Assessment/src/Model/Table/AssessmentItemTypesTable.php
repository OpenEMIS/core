<?php
namespace Assessment\Model\Table;

use App\Model\Table\AppTable;

class AssessmentItemTypesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('AssessmentItems', ['className' => 'Assessment.AssessmentItems']);
	}
}
