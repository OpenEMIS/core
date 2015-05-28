<?php
namespace Rubric\Model\Table;

use App\Model\Table\AppTable;

class RubricCriteriasTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('RubricSections', ['className' => 'Rubric.RubricSections']);
		$this->hasMany('RubricCriteriaOptions', ['className' => 'Rubric.RubricCriteriaOptions']);
	}
}
