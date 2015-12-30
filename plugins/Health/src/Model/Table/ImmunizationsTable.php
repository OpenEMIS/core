<?php
namespace Health\Model\Table;

use App\Model\Table\AppTable;

class ImmunizationsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('user_health_immunizations');
		parent::initialize($config);

		$this->belongsTo('ImmunizationTypes', ['className' => 'Health.ImmunizationTypes', 'foreignKey' => 'health_immunization_type_id']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

		$this->addBehavior('Health.Health');
	}
}
