<?php
namespace Health\Model\Table;

use App\Model\Table\AppTable;

class ConditionsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('health_conditions');
		parent::initialize($config);

		$this->hasMany('Families', ['className' => 'Health.Families', 'foreignKey' => 'health_condition_id']);
		$this->hasMany('Histories', ['className' => 'Health.Histories', 'foreignKey' => 'health_condition_id']);

		$this->addBehavior('OpenEmis.OpenEmis');
		$this->addBehavior('ControllerAction.ControllerAction', [
			'actions' => ['remove' => 'transfer'],
			'fields' => ['excludes' => ['modified_user_id', 'created_user_id']]
		]);
	}
}
