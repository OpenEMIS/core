<?php
namespace Security\Model\Table;

use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

class SecurityGroupUsersTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);
		$this->belongsTo('SecurityGroups', ['className' => 'Security.UserGroups']);
		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
	}

	public function insertSecurityRoleForInstitution($data) {
		$institutionId = (array_key_exists('institution_id', $data))? $data['institution_id']: null;
		$securityUserId = (array_key_exists('security_user_id', $data))? $data['security_user_id']: null;
		$securityRoleId = (array_key_exists('security_role_id', $data))? $data['security_role_id']: null;

		if (!is_null($institutionId) && !is_null($securityUserId) && !is_null($securityRoleId)) {
			$Institution = TableRegistry::get('Institution.Institutions');
			$institutionQuery = $Institution
				->find()
				->where([$Institution->aliasField($Institution->primaryKey()) => $institutionId])
				->first()
				;

			if ($institutionQuery) {
				$securityGroupId = (isset($institutionQuery->security_group_id))? $institutionQuery->security_group_id: null;
			}

			if (!is_null($securityGroupId)) {
				$newEntity = $this->newEntity(
					[
						'security_user_id' => $securityUserId,
						'security_role_id' => $securityRoleId,
						'security_group_id' => $securityGroupId,
					]
				);
				return $this->save($newEntity);
			} else {
				return false;
			}

		} else {
			return false;
		}
	}

	public function checkEditGroup($userId, $securityGroupId, $field, $roleType = 'system') {
		// Role type system: System roles
		// Role type user: User roles

		if ($roleType == 'system') {
			// System role
			$securityFunctionId = 5024;
		} else if ($roleType == 'user') {
			// User role
			$securityFunctionId = 5025;
		} else {
			// Always false
			$securityFunctionId = 0;
		}
		$results = $this
			->find()
			->innerJoin(
				['SecurityRoleFunctions' => 'security_role_functions'],
				[
					'SecurityRoleFunctions.security_role_id = '.$this->aliasField('security_role_id'),
					'SecurityRoleFunctions.security_function_id' => $securityFunctionId,
					'SecurityRoleFunctions.'.$field => 1
				]
			)
			->where([$this->aliasField('security_user_id') => $userId, $this->aliasField('security_group_id') => $securityGroupId])
			->hydrate(false)
			->toArray();
		return $results;
	}
}
