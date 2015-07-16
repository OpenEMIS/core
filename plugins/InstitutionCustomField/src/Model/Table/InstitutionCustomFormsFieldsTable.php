<?php
namespace InstitutionCustomField\Model\Table;

use CustomField\Model\Table\CustomFormsFieldsTable;

class InstitutionCustomFormsFieldsTable extends CustomFormsFieldsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomForms', ['className' => 'InstitutionCustomField.InstitutionCustomForms']);
		$this->belongsTo('CustomFields', ['className' => 'InstitutionCustomField.InstitutionCustomFields']);
	}
}
