<?php
namespace StaffCustomField\Model\Table;

use CustomField\Model\Table\CustomTableRowsTable;

class StaffCustomTableRowsTable extends CustomTableRowsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'StaffCustomField.StaffCustomFields', 'foreignKey' => 'staff_custom_field_id']);
		$this->hasMany('CustomTableCells', ['className' => 'StaffCustomField.StaffCustomTableCells', 'foreignKey' => 'staff_custom_table_row_id', 'dependent' => true]);
		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'staff_custom_field_id',
			]);
		}
	}
}
