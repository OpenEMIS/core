<?php
namespace StaffCustomField\Model\Table;

use CustomField\Model\Table\CustomFieldOptionsTable;

class StaffCustomFieldOptionsTable extends CustomFieldOptionsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'StaffCustomField.StaffCustomFields', 'foreignKey' => 'staff_custom_field_id']);
		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'staff_custom_field_id',
			]);
		}
	}
}
