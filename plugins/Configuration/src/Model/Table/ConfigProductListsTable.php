<?php
namespace Configuration\Model\Table;

use App\Model\Table\ControllerActionTable;

class ConfigProductListsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->table('config_product_lists');
		parent::initialize($config);
        $this->addBehavior('Configuration.ConfigItems');
	}

}
