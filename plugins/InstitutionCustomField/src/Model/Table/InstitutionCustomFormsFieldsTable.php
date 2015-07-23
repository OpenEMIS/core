<?php
namespace InstitutionCustomField\Model\Table;

use CustomField\Model\Table\CustomFormsFieldsTable;

class InstitutionCustomFormsFieldsTable extends CustomFormsFieldsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomForms', ['className' => 'InstitutionCustomField.InstitutionCustomForms', 'foreignKey' => 'institution_custom_form_id']);
		$this->belongsTo('CustomFields', ['className' => 'InstitutionCustomField.InstitutionCustomFields', 'foreignKey' => 'institution_custom_field_id']);
	}
}
