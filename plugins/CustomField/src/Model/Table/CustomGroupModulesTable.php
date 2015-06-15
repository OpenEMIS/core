<?php
namespace CustomField\Model\Table;

use App\Model\Table\AppTable;

class CustomGroupModulesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomGroups', ['className' => 'CustomField.CustomGroups']);
		$this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
	}
}
