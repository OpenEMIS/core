<?php
namespace Rubric\Model\Table;

use App\Model\Table\AppTable;

class RubricCriteriaOptionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('RubricCriterias', ['className' => 'Rubric.RubricCriterias']);
	}
}
