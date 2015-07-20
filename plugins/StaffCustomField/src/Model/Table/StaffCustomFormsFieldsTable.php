<?php
namespace StaffCustomField\Model\Table;

use CustomField\Model\Table\CustomFormsFieldsTable;

class StaffCustomFormsFieldsTable extends CustomFormsFieldsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomForms', ['className' => 'StaffCustomField.StaffCustomForms', 'foreignKey' => 'staff_custom_form_id']);
		$this->belongsTo('CustomFields', ['className' => 'StaffCustomField.StaffCustomFields', 'foreignKey' => 'staff_custom_field_id']);
	}
}
