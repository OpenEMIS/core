<?php
namespace Security\Model\Table;

use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\Log\Log;

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

	public function checkEditGroup($userId, $securityGroupId, $field) {
		// Security function: Group
		$securityFunctionId = 5023;
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

	public function getRolesByUserAndGroup($groupIds, $userId) {
		if (!empty($groupIds)) {
			$securityRoles = $this
				->find('list', [
					'keyField' => 'security_role_id',
					'valueField' => 'security_role_id'
				])
				->innerJoinWith('SecurityRoles')
				->where([
					$this->aliasField('security_user_id') => $userId,
					$this->aliasField('security_group_id').' IN ' => $groupIds
				])
				->order('SecurityRoles.order')
				->group([$this->aliasField('security_role_id')])
				->select([$this->aliasField('security_role_id')])
				->hydrate(false)
				->toArray();
			return $securityRoles;
		} else {
			return [];
		}
	}

	public function findRoleByInstitution(Query $query, array $options)
	{
		$userId = $options['security_user_id'];
		$institutionId = $options['institution_id'];
		$query
			->innerJoin(['SecurityGroupInstitutions' => 'security_group_institutions'], [
				'SecurityGroupInstitutions.security_group_id = '.$this->aliasField('security_group_id'), 
				'SecurityGroupInstitutions.institution_id' => $institutionId
			])
			->where([$this->aliasField('security_user_id') => $userId])
			->distinct([$this->aliasField('security_role_id')]);
		return $query;
	}

	public function findUserList(Query $query, array $options)
	{
		$where = array_key_exists('where', $options) ? $options['where'] : [];
		$area = array_key_exists('area', $options) ? $options['area'] : null;

		$query->find('list', ['keyField' => $this->Users->aliasField('id'), 'valueField' => $this->Users->aliasField('name_with_id')])
			->select([
				$this->Users->aliasField('id'),
	            $this->Users->aliasField('openemis_no'),
	            $this->Users->aliasField('first_name'),
	            $this->Users->aliasField('middle_name'),
	            $this->Users->aliasField('third_name'),
	            $this->Users->aliasField('last_name'),
	            $this->Users->aliasField('preferred_name')
			])
			->contain([$this->Users->alias()])
			->group([$this->Users->aliasField('id')]);

		if (!empty($where)) {
			$query->where($where);
		}

		if (!is_null($area)) {
			$query
				->matching('SecurityGroups.Areas', function ($q) use ($area) {
		            return $q->where([
		                'Areas.lft <= ' => $area->lft,
		                'Areas.rght >= ' => $area->lft
		            ]);
		        });
		}

		return $query;
	}

	public function findAssignedStaff(Query $query, array $options)
	{
		$institutionId = array_key_exists('institution_id', $options) ? $options['institution_id'] : null;
		$securityRoles = array_key_exists('security_roles', $options) ? $options['security_roles'] : [];

		if (!is_null($institutionId) && !empty($securityRoles)) {
			$StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
			$Staff = TableRegistry::get('Institution.Staff');
            $InstitutionPositions = TableRegistry::get('Institution.InstitutionPositions');
            $StaffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');

			$assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');
			$today = date('Y-m-d');

			$query
				->innerJoin(
	                [$Staff->alias() => $Staff->table()],
	                [
	                    $Staff->aliasField('staff_id = ') . $this->aliasField('security_user_id'),
	                    $Staff->aliasField('institution_id') => $institutionId,
	                    $Staff->aliasField('staff_status_id') => $assignedStatus,
	                    'OR' => [
	                        [
	                            $Staff->aliasField('end_date IS NULL'),
	                            $Staff->aliasField('start_date <= ') => $today
	                        ],
	                        [
	                            $Staff->aliasField('end_date IS NOT NULL'),
	                            $Staff->aliasField('start_date <= ') => $today,
	                            $Staff->aliasField('end_date >= ') => $today
	                        ]
	                    ]
	                ]
	            )
	            ->innerJoin(
	                [$InstitutionPositions->alias() => $InstitutionPositions->table()],
	                [
	                    $InstitutionPositions->aliasField('id = ') . $Staff->aliasField('institution_position_id'),
	                    $InstitutionPositions->aliasField('institution_id') => $institutionId
	                ]
	            )
	            ->innerJoin(
	                [$StaffPositionTitles->alias() => $StaffPositionTitles->table()],
	                [
	                    $StaffPositionTitles->aliasField('id = ') . $InstitutionPositions->aliasField('staff_position_title_id'),
	                    $StaffPositionTitles->aliasField('security_role_id IN ') => $securityRoles
	                ]
	            );
		}

		return $query;
	}
}
