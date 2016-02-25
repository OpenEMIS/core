<?php
namespace StudentCustomField\Model\Table;

use CustomField\Model\Table\CustomFieldsTable;

class StudentCustomFieldsTable extends CustomFieldsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('CustomFieldOptions', ['className' => 'StudentCustomField.StudentCustomFieldOptions', 'dependent' => true]);
		$this->hasMany('CustomTableColumns', ['className' => 'StudentCustomField.StudentCustomTableColumns', 'dependent' => true]);
		$this->hasMany('CustomTableRows', ['className' => 'StudentCustomField.StudentCustomTableRows', 'dependent' => true]);
		$this->hasMany('CustomFieldValues', ['className' => 'InstitutionCustomField.InstitutionCustomFieldValues', 'dependent' => true]);
		$this->hasMany('CustomTableCells', ['className' => 'InstitutionCustomField.InstitutionCustomTableCells', 'dependent' => true]);
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
