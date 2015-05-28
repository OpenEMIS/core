<?php
namespace Rubric\Model\Table;

use App\Model\Table\AppTable;

class RubricTemplateOptionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('RubricTemplates', ['className' => 'Rubric.RubricTemplates']);
	}
}
