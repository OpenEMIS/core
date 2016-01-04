<?php
namespace Health\Model\Table;

use App\Model\Table\AppTable;

class TestTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('health_test_types');
		parent::initialize($config);

		$this->hasMany('Tests', ['className' => 'Health.Tests', 'foreignKey' => 'health_test_type_id']);
	}
}
