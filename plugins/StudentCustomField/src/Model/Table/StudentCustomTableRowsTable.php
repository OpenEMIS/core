<?php
namespace StudentCustomField\Model\Table;

use CustomField\Model\Table\CustomTableRowsTable;

class StudentCustomTableRowsTable extends CustomTableRowsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'StudentCustomField.StudentCustomFields', 'foreignKey' => 'student_custom_field_id']);
	}
}
