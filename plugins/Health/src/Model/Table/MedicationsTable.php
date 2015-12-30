<?php
namespace Health\Model\Table;

use App\Model\Table\AppTable;

class MedicationsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('user_health_medications');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

		$this->addBehavior('Health.Health');
	}
}
