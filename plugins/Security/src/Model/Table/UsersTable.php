<?php
namespace Security\Model\Table;

use User\Model\Table\UsersTable as BaseTable;
use Cake\Validation\Validator;

class UsersTable extends BaseTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->entityClass('User.User');
		$this->addBehavior('Security.User');

		$this->belongsToMany('SecurityRoles', [
			'className' => 'Security.SecurityRoles',
			'foreignKey' => 'security_role_id',
			'targetForeignKey' => 'security_user_id',
			'through' => 'Security.SecurityGroupUsers',
			'dependent' => true
		]);	
	}

	// autocomplete used for UserGroups
	public function autocomplete($search) {
		$search = sprintf('%%%s%%', $search);

		$list = $this
			->find()
			->where([
				'OR' => [
					$this->aliasField('openemis_no') . ' LIKE' => $search,
					$this->aliasField('first_name') . ' LIKE' => $search,
					$this->aliasField('middle_name') . ' LIKE' => $search,
					$this->aliasField('third_name') . ' LIKE' => $search,
					$this->aliasField('last_name') . ' LIKE' => $search
				]
			])
			->order([$this->aliasField('first_name')])
			->all();
		
		$data = array();
		foreach($list as $obj) {
			$data[] = [
				'label' => sprintf('%s - %s', $obj->openemis_no, $obj->name),
				'value' => $obj->id
			];
		}
		return $data;
	}
}
