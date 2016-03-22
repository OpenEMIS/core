<?php
namespace StaffCustomField\Model\Table;

use CustomField\Model\Table\CustomFieldValuesTable;

class StaffCustomFieldValuesTable extends CustomFieldValuesTable {
	protected $extra = ['scope' => 'staff_custom_field_id'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'StaffCustomField.StaffCustomFields', 'foreignKey' => 'staff_custom_field_id']);
		$this->belongsTo('CustomRecords', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
	}
}
