<?php
namespace StudentCustomField\Model\Table;

use CustomField\Model\Table\CustomTableCellsTable;

class StudentCustomTableCellsTable extends CustomTableCellsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'StudentCustomField.StudentCustomFields', 'foreignKey' => 'student_custom_field_id']);
		$this->belongsTo('CustomRecords', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('CustomTableRows', ['className' => 'StudentCustomField.StaffCustomTableRows', 'foreignKey' => 'student_custom_table_row_id']);
		$this->belongsTo('CustomTableColumns', ['className' => 'StudentCustomField.StaffCustomTableColumns', 'foreignKey' => 'student_custom_table_column_id']);
	}
}
