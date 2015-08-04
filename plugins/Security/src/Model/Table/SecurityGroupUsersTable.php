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
		$institutionSiteId = (array_key_exists('institution_site_id', $data))? $data['institution_site_id']: null;
		$securityUserId = (array_key_exists('security_user_id', $data))? $data['security_user_id']: null;
		$securityRoleId = (array_key_exists('security_role_id', $data))? $data['security_role_id']: null;

		if (!is_null($institutionSiteId) && !is_null($securityUserId) && !is_null($securityRoleId)) {
			$Institution = TableRegistry::get('Institution.Institutions');
			$institutionQuery = $Institution
				->find()
				->where([$Institution->aliasField($Institution->primaryKey()) => $institutionSiteId])
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
}
