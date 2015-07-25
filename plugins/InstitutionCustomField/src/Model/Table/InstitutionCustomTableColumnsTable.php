<?php
namespace InstitutionCustomField\Model\Table;

use CustomField\Model\Table\CustomTableColumnsTable;

class InstitutionCustomTableColumnsTable extends CustomTableColumnsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'InstitutionCustomField.InstitutionCustomFields', 'foreignKey' => 'institution_custom_field_id']);
	}
}
