<?php
namespace StudentCustomField\Model\Table;

use CustomField\Model\Table\CustomFieldValuesTable;

class StudentCustomFieldValuesTable extends CustomFieldValuesTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'StudentCustomField.StudentCustomFields', 'foreignKey' => 'student_custom_field_id']);
		$this->belongsTo('CustomRecords', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
	}
}
