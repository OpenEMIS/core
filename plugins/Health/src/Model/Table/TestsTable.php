<?php
namespace Health\Model\Table;

use Cake\Event\Event;
use App\Model\Table\AppTable;

class TestsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('user_health_tests');
		parent::initialize($config);

		$this->belongsTo('TestTypes', ['className' => 'Health.TestTypes', 'foreignKey' => 'health_test_type_id']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

		$this->addBehavior('Health.Health');
	}

	public function addEditBeforeAction(Event $event) {
		$this->ControllerAction->field('health_test_type_id', ['type' => 'select']);
	}
}