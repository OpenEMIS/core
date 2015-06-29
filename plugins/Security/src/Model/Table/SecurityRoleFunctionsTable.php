<?php
namespace Security\Model\Table;

use App\Model\Table\AppTable;

class SecurityRoleFunctionsTable extends AppTable {
	public function initialize(array $config) {
		$this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);
		$this->belongsTo('SecurityFunctions', ['className' => 'Security.SecurityFunctions']);
	}
}
