<?php
namespace CustomField\Model\Table;

use App\Model\Table\AppTable;

class CustomTableRowsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'CustomField.CustomFields']);
	}
}
