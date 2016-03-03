<?php
namespace StaffCustomField\Model\Table;

use CustomField\Model\Table\CustomTableCellsTable;

class StaffCustomTableCellsTable extends CustomTableCellsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'StaffCustomField.StaffCustomFields', 'foreignKey' => 'staff_custom_field_id']);
		$this->belongsTo('CustomRecords', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('CustomTableRows', ['className' => 'StaffCustomField.StaffCustomTableRows', 'foreignKey' => 'staff_custom_table_row_id']);
		$this->belongsTo('CustomTableColumns', ['className' => 'StaffCustomField.StaffCustomTableColumns', 'foreignKey' => 'staff_custom_table_column_id']);
	}
}
