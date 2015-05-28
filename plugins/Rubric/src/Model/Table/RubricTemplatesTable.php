<?php
namespace Rubric\Model\Table;

use App\Model\Table\AppTable;

class RubricTemplatesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('RubricSections', ['className' => 'Rubric.RubricSections']);
		$this->hasMany('RubricTemplateOptions', ['className' => 'Rubric.RubricTemplateOptions']);
	}
}
