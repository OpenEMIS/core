<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;

class StudentCustomFieldValuesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'CustomField.CustomFields']);
		$this->belongsTo('CustomRecords', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
	}
}
