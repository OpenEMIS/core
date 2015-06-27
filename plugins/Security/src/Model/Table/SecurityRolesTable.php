<?php
namespace Security\Model\Table;

use App\Model\Table\AppTable;

class SecurityRolesTable extends AppTable {
	public function initialize(array $config) {
		$this->belongsTo('SecurityGroups', ['className' => 'Security.SecurityGroups']);

		$this->belongsToMany('SecurityFunctions', [
			'className' => 'Security.SecurityFunctions',
			'through' => 'Security.SecurityRoleFunctions'
		]);
	}
}
