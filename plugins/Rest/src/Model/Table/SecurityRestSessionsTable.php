<?php
namespace Rest\Model\Table;

use App\Model\Table\ControllerActionTable;

class SecurityRestSessionsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->table('security_rest_sessions');
		parent::initialize($config);
	}
}
