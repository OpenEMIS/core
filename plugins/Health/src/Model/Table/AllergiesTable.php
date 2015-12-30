<?php
namespace Health\Model\Table;

use App\Model\Table\AppTable;

class AllergiesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('user_health_allergies');
		parent::initialize($config);

		$this->belongsTo('AllergyTypes', ['className' => 'Health.AllergyTypes', 'foreignKey' => 'health_allergy_type_id']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

		$this->addBehavior('Health.Health');
	}
}
