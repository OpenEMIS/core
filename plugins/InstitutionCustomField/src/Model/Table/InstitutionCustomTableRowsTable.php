<?php
namespace InstitutionCustomField\Model\Table;

use CustomField\Model\Table\CustomTableRowsTable;

class InstitutionCustomTableRowsTable extends CustomTableRowsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'InstitutionCustomField.InstitutionCustomFields', 'foreignKey' => 'institution_custom_field_id']);
	}
}
