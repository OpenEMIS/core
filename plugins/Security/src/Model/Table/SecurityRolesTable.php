<?php
namespace Security\Model\Table;

use App\Model\Table\AppTable;

class SecurityRolesTable extends AppTable {
	public function initialize(array $config) {
		$this->belongsTo('ModifiedUser', [
			'className' => 'SecurityUsers',
			'fields' => array('ModifiedUser.first_name', 'ModifiedUser.last_name'),
			'foreignKey' => 'modified_user_id'
		]);
		$this->belongsTo('CreatedUser', [
			'className' => 'SecurityUsers',
			'fields' => array('CreatedUser.first_name', 'CreatedUser.last_name'),
			'foreignKey' => 'created_user_id'
		]);
	}
}
