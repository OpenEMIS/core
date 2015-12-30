<?php
namespace Health\Model\Table;

use App\Model\Table\AppTable;

class ConditionsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('health_conditions');
		parent::initialize($config);
		$this->hasMany('Families', ['className' => 'Health.Families', 'foreignKey' => 'health_condition_id', 'dependent' => true]);
		$this->hasMany('Histories', ['className' => 'Health.Histories', 'foreignKey' => 'health_condition_id', 'dependent' => true]);
	}
}
