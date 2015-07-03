<?php
namespace Staff\Model\Table;

use CustomField\Model\Table\CustomTableCellsTable;

class StaffCustomTableCellsTable extends CustomTableCellsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'CustomField.CustomFields']);
		$this->belongsTo('CustomRecords', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
	}
}
