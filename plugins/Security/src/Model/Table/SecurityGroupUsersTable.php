<?php
namespace Security\Model\Table;

use App\Model\Table\AppTable;

class SecurityGroupUsersTable extends AppTable {
	public function initialize(array $config) {
		$this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);
		$this->belongsTo('SecurityGroups', ['className' => 'Security.SecurityGroups']);
		$this->belongsTo('Users', ['className' => 'User.Users']);
	}
}
