<?php
namespace InstitutionCustomField\Model\Table;

use CustomField\Model\Table\CustomFormsFiltersTable;

class InstitutionCustomFormsFiltersTable extends CustomFormsFiltersTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomForms', ['className' => 'InstitutionCustomField.InstitutionCustomForms']);
		$this->belongsTo('CustomFilters', ['className' => 'FieldOption.FieldOptionValues']);
	}
}
