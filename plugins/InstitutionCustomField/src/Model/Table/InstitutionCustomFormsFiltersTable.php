<?php
namespace InstitutionCustomField\Model\Table;

use CustomField\Model\Table\CustomFormsFiltersTable;

class InstitutionCustomFormsFiltersTable extends CustomFormsFiltersTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomForms', ['className' => 'InstitutionCustomField.InstitutionCustomForms', 'foreignKey' => 'institution_custom_form_id']);
		$this->belongsTo('CustomFilters', ['className' => 'FieldOption.InstitutionTypes', 'foreignKey' => 'institution_custom_filter_id']);
	}
}
