<?php
namespace StaffCustomField\Model\Table;

use CustomField\Model\Table\CustomTableColumnsTable;

class StaffCustomTableColumnsTable extends CustomTableColumnsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'StaffCustomField.StaffCustomFields', 'foreignKey' => 'staff_custom_field_id']);
	}
}
