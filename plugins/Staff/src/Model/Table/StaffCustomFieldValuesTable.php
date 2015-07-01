<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;

class StaffCustomFieldValuesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'CustomField.CustomFields']);
		$this->belongsTo('CustomRecords', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
	}
}