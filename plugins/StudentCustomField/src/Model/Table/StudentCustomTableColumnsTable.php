<?php
namespace StudentCustomField\Model\Table;

use CustomField\Model\Table\CustomTableColumnsTable;

class StudentCustomTableColumnsTable extends CustomTableColumnsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'StudentCustomField.StudentCustomFields', 'foreignKey' => 'student_custom_field_id']);
	}
}
