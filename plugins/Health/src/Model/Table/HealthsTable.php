<?php
namespace Health\Model\Table;

use App\Model\Table\AppTable;

class HealthsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('user_healths');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

		$this->addBehavior('Health.Health');
	}
}
