<?php
namespace Health\Model\Table;

use App\Model\Table\ControllerActionTable;

class ConditionsTable extends ControllerActionTable {
	public function initialize(array $config)
	{
		$this->addBehavior('FieldOption.FieldOption');
		$this->table('health_conditions');
		parent::initialize($config);

		$this->hasMany('Families', ['className' => 'Health.Families', 'foreignKey' => 'health_condition_id']);
		$this->hasMany('Histories', ['className' => 'Health.Histories', 'foreignKey' => 'health_condition_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}
}
