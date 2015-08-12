<?php
namespace Security\Model\Table;

use Cake\ORM\Query;
use App\Model\Table\AppTable;

class SecurityFunctionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsToMany('SecurityRoles', [
			'className' => 'Security.SecurityRoles',
			'through' => 'Security.SecurityRoleFunctions'
		]);
	}

	public function findPermissions(Query $query, $options) {
		$roleId = $options['roleId'];
		$module = $options['module'];

		$query
		->find('visible')
		->select([
			'SecurityFunctions.id', 'SecurityFunctions.name', 'SecurityFunctions.controller', 
			'SecurityFunctions.module', 'SecurityFunctions.category', 
			'SecurityFunctions._view', 'SecurityFunctions._add', 'SecurityFunctions._edit',
			'SecurityFunctions._delete', 'SecurityFunctions._execute',
			'Permissions.id', 'Permissions._view', 'Permissions._add', 'Permissions._edit',
			'Permissions._delete', 'Permissions._execute'
		])
		->leftJoin(
			['Permissions' => 'security_role_functions'], 
			['Permissions.security_function_id = SecurityFunctions.id', 'Permissions.security_role_id = ' . $roleId]
		)
		->where(['SecurityFunctions.module' => $module])
		->order([
			'SecurityFunctions.order'
		])
		;
		return $query;
	}
}
