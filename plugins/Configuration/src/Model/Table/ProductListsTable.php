<?php
namespace Configuration\Model\Table;

use App\Model\Table\ControllerActionTable;

class ProductListsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->table('config_product_lists');
		parent::initialize($config);
	}

}
