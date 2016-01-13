<?php
namespace Health\Model\Table;

use App\Model\Table\AppTable;

class RelationshipsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('health_relationships');
		parent::initialize($config);

		$this->hasMany('Families', ['className' => 'Health.Families', 'foreignKey' => 'health_relationship_id']);
	}
}
