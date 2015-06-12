<?php
namespace Security\Model\Table;

use App\Model\Table\AppTable;

class SecurityGroupsTable extends AppTable {
	public function initialize(array $config) {
		$this->hasMany('SecurityRoles', ['className' => 'Security.SecurityRoles']);
	}
}
