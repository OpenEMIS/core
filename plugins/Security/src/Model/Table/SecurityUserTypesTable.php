<?php
namespace Security\Model\Table;

use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

class SecurityUserTypesTable extends AppTable {
	const STUDENT = 1;
	const STAFF = 2;
	const GUARDIAN = 3;

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
	}
}
