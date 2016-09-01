<?php
namespace StudentCustomField\Model\Table;

use CustomField\Model\Table\CustomFieldsTable;

class StudentCustomFieldsTable extends CustomFieldsTable {
	public function initialize(array $config) {
		$this->supportedFieldTypes = $this->getSupportedFieldTypesByModel('Student.Students');
		parent::initialize($config);
		$this->hasMany('CustomFieldOptions', ['className' => 'StudentCustomField.StudentCustomFieldOptions', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableColumns', ['className' => 'StudentCustomField.StudentCustomTableColumns', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableRows', ['className' => 'StudentCustomField.StudentCustomTableRows', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomFieldValues', ['className' => 'StudentCustomField.StudentCustomFieldValues', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableCells', ['className' => 'StudentCustomField.StudentCustomTableCells', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('CustomForms', [
			'className' => 'StudentCustomField.StudentCustomForms',
			'joinTable' => 'student_custom_forms_fields',
			'foreignKey' => 'student_custom_field_id',
			'targetForeignKey' => 'student_custom_form_id',
			'through' => 'StudentCustomField.StudentCustomFormsFields',
			'dependent' => true
		]);
	}
}
