<?php
namespace Health\Model\Table;

use App\Model\Table\AppTable;

class HistoriesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('user_health_histories');
		parent::initialize($config);

		$this->belongsTo('Conditions', ['className' => 'Health.Conditions', 'foreignKey' => 'health_condition_id']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

		$this->addBehavior('Health.Health');
	}
}
