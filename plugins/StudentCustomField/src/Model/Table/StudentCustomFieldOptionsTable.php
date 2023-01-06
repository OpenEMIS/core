<?php
namespace StudentCustomField\Model\Table;

use CustomField\Model\Table\CustomFieldOptionsTable;

class StudentCustomFieldOptionsTable extends CustomFieldOptionsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'StudentCustomField.StudentCustomFields', 'foreignKey' => 'student_custom_field_id']);
		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'student_custom_field_id',
			]);
		}
	}
}
