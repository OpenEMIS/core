<?php
namespace StaffCustomField\Model\Table;

use CustomField\Model\Table\CustomFieldsTable;

class StaffCustomFieldsTable extends CustomFieldsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('CustomFieldOptions', ['className' => 'StaffCustomField.StaffCustomFieldOptions', 'dependent' => true]);
		$this->hasMany('CustomTableColumns', ['className' => 'StaffCustomField.StaffCustomTableColumns', 'dependent' => true]);
		$this->hasMany('CustomTableRows', ['className' => 'StaffCustomField.StaffCustomTableRows', 'dependent' => true]);
		$this->hasMany('CustomFieldValues', ['className' => 'StaffCustomField.StaffCustomFieldValues', 'dependent' => true]);
		$this->hasMany('CustomTableCells', ['className' => 'StaffCustomField.StaffCustomTableCells', 'dependent' => true]);
		$this->belongsToMany('CustomForms', [
			'className' => 'StaffCustomField.StaffCustomForms',
			'joinTable' => 'staff_custom_forms_fields',
			'foreignKey' => 'staff_custom_field_id',
			'targetForeignKey' => 'staff_custom_form_id',
			'through' => 'StaffCustomField.StaffCustomFormsFields',
			'dependent' => true
		]);
	}
}
