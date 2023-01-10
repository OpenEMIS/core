<?php
namespace StudentCustomField\Model\Table;

use CustomField\Model\Table\CustomFormsFieldsTable;

class StudentCustomFormsFieldsTable extends CustomFormsFieldsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomForms', ['className' => 'StudentCustomField.StudentCustomForms', 'foreignKey' => 'student_custom_form_id']);
		$this->belongsTo('CustomFields', ['className' => 'StudentCustomField.StudentCustomFields', 'foreignKey' => 'student_custom_field_id']);
	}
}
