<?php
namespace InstitutionCustomField\Model\Table;

use CustomField\Model\Table\CustomFieldsTable;

class InstitutionCustomFieldsTable extends CustomFieldsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('CustomFieldOptions', ['className' => 'InstitutionCustomField.InstitutionCustomFieldOptions', 'dependent' => true]);
		$this->hasMany('CustomTableColumns', ['className' => 'InstitutionCustomField.InstitutionCustomTableColumns', 'dependent' => true]);
		$this->hasMany('CustomTableRows', ['className' => 'InstitutionCustomField.InstitutionCustomTableRows', 'dependent' => true]);
		$this->hasMany('CustomFieldValues', ['className' => 'InstitutionCustomField.InstitutionCustomFieldValues', 'dependent' => true]);
		$this->hasMany('CustomTableCells', ['className' => 'InstitutionCustomField.InstitutionCustomTableCells', 'dependent' => true]);
		$this->belongsToMany('CustomForms', [
			'className' => 'InstitutionCustomField.InstitutionCustomForms',
			'joinTable' => 'institution_custom_forms_fields',
			'foreignKey' => 'institution_custom_field_id',
			'targetForeignKey' => 'institution_custom_form_id',
			'through' => 'InstitutionCustomField.InstitutionCustomFormsFields',
			'dependent' => true
		]);
	}
}
