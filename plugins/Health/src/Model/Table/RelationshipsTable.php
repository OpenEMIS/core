<?php
namespace Health\Model\Table;

use App\Model\Table\ControllerActionTable;

class RelationshipsTable extends ControllerActionTable {
	public function initialize(array $config)
	{
		$this->addBehavior('FieldOption.FieldOption');
		$this->table('health_relationships');
		parent::initialize($config);

		$this->hasMany('Families', ['className' => 'Health.Families', 'foreignKey' => 'health_relationship_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}
}
