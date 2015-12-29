<?php
namespace Student\Model\Table;

use CustomField\Model\Table\CustomFieldValuesTable;

class StudentCustomFieldValuesTable extends CustomFieldValuesTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'CustomField.CustomFields']);
		$this->belongsTo('CustomRecords', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
	}
}
