<?php
namespace InstitutionCustomField\Model\Table;

use CustomField\Model\Table\CustomFieldsTable;

class InstitutionCustomFieldsTable extends CustomFieldsTable {
	public function initialize(array $config) {
		$this->supportedFieldTypes = $this->getSupportedFieldTypesByModel('Institution.Institutions');
		parent::initialize($config);
		$this->hasMany('CustomFieldOptions', ['className' => 'InstitutionCustomField.InstitutionCustomFieldOptions', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableColumns', ['className' => 'InstitutionCustomField.InstitutionCustomTableColumns', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableRows', ['className' => 'InstitutionCustomField.InstitutionCustomTableRows', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomFieldValues', ['className' => 'InstitutionCustomField.InstitutionCustomFieldValues', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableCells', ['className' => 'InstitutionCustomField.InstitutionCustomTableCells', 'dependent' => true, 'cascadeCallbacks' => true]);
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
