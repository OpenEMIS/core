<?php
namespace Security\Model\Table;

use App\Model\Table\AppTable;

class SecurityFunctionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsToMany('SecurityRoles', [
			'className' => 'Security.SecurityRoles',
			'through' => 'Security.SecurityRoleFunctions'
		]);
	}
}
