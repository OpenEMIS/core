<?php
namespace CustomField\Model\Table;

use App\Model\Table\AppTable;

class CustomModulesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Parents', ['className' => 'CustomField.CustomModules']);
	}
}
