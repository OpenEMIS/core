<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Log\Log;

class SecurityGroupUsersTable extends AppTable {
	// Workflow Steps - category
	const TO_DO = 1;
	const IN_PROGRESS = 2;
	const DONE = 3;

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);
		$this->belongsTo('SecurityGroups', ['className' => 'Security.UserGroups']);
		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
		$this->addBehavior('Restful.RestfulAccessControl', [
            'Results' => ['index']
        ]);
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		$models = TableRegistry::get('Workflow.WorkflowModels')->find()->all();
		$broadcaster = $this;
		$listeners = [];
		foreach ($models as $key => $obj) {
			$listeners[] = TableRegistry::get($obj->model);
		}

		if (!empty($listeners)) {
			$this->dispatchEventToModels('Model.SecurityGroupUsers.afterSave', [$entity], $broadcaster, $listeners);
		}
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		$models = TableRegistry::get('Workflow.WorkflowModels')->find()->all();
		$broadcaster = $this;
		$listeners = [];
		foreach ($models as $key => $obj) {
			$listeners[] = TableRegistry::get($obj->model);
		}

		if (!empty($listeners)) {
			$this->dispatchEventToModels('Model.SecurityGroupUsers.afterDelete', [$entity], $broadcaster, $listeners);
		}
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

	// IMPORTANT: when editing this method, need to consider impact on getFirstAssignee()
	public function getAssigneeList($params=[])
	{
		$isSchoolBased = array_key_exists('is_school_based', $params) ? $params['is_school_based'] : null;
		$stepId = array_key_exists('workflow_step_id', $params) ? $params['workflow_step_id'] : null;
		$institutionId = array_key_exists('institution_id', $params) ? $params['institution_id'] : null;

		Log::write('debug', 'Is School Based: ' . $isSchoolBased);
        Log::write('debug', 'Workflow Step Id: ' . $stepId);

        $assigneeOptions = [];
        if (!is_null($stepId)) {
            $WorkflowStepsRoles = TableRegistry::get('Workflow.WorkflowStepsRoles');
            $stepRoles = $WorkflowStepsRoles->getRolesByStep($stepId);
            Log::write('debug', 'Roles By Step:');
            Log::write('debug', $stepRoles);

            if (!empty($stepRoles)) {
                $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
                $Areas = TableRegistry::get('Area.Areas');
                $Institutions = TableRegistry::get('Institution.Institutions');

                if ($isSchoolBased) {
                    if (is_null($institutionId)) {
                    	Log::write('debug', 'Institution Id not found.');
                    } else {
                        $institutionObj = $Institutions->find()->where([$Institutions->aliasField('id') => $institutionId])->contain(['Areas'])->first();
                        $securityGroupId = $institutionObj->security_group_id;
                        $areaObj = $institutionObj->area;

                        Log::write('debug', 'Institution Id: ' . $institutionId);
                        Log::write('debug', 'Security Group Id: ' . $securityGroupId);
                        Log::write('debug', 'Institution Area:');
                        Log::write('debug', $areaObj);

                        // School based assignee
                        $where = [
                            $SecurityGroupUsers->aliasField('security_group_id') => $securityGroupId,
                            $SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles
                        ];
                        $schoolBasedAssigneeQuery = $SecurityGroupUsers
                            ->find('userList', ['where' => $where]);

                        Log::write('debug', 'School based assignee query:');
                        Log::write('debug', $schoolBasedAssigneeQuery->sql());

                        $schoolBasedAssigneeOptions = $schoolBasedAssigneeQuery->toArray();
                        Log::write('debug', 'School based assignee:');
                        Log::write('debug', $schoolBasedAssigneeOptions);
                        // End

                        // Region based assignee
                        $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                        $regionBasedAssigneeQuery = $SecurityGroupUsers
                            ->find('userList', ['where' => $where, 'area' => $areaObj]);

                        Log::write('debug', 'Region based assignee query:');
                        Log::write('debug', $regionBasedAssigneeQuery->sql());

                        $regionBasedAssigneeOptions = $regionBasedAssigneeQuery->toArray();
                        Log::write('debug', 'Region based assignee:');
                        Log::write('debug', $regionBasedAssigneeOptions);
                        // End

                        $assigneeOptions = $schoolBasedAssigneeOptions + $regionBasedAssigneeOptions;                  
                    }
                } else {
                    $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                    $assigneeQuery = $SecurityGroupUsers
                        ->find('userList', ['where' => $where]);

                    Log::write('debug', 'Non-School based assignee query:');
                    Log::write('debug', $assigneeQuery->sql());

                    $assigneeOptions = $assigneeQuery->toArray();
                }                
            }
        }

        return $assigneeOptions;
	}

	// IMPORTANT: when editing this method, need to consider impact on getAssigneeList()
	public function getFirstAssignee($params=[])
	{
		$isSchoolBased = array_key_exists('is_school_based', $params) ? $params['is_school_based'] : null;
		$stepId = array_key_exists('workflow_step_id', $params) ? $params['workflow_step_id'] : null;
		$institutionId = array_key_exists('institution_id', $params) ? $params['institution_id'] : null;
		$category = array_key_exists('category', $params) ? $params['category'] : null;
		$createdUserId = array_key_exists('created_user_id', $params) ? $params['created_user_id'] : null;

		Log::write('debug', 'Is School Based: ' . $isSchoolBased);
        Log::write('debug', 'Step Id: ' . $stepId);

		$assigneeId = 0;
		if (!is_null($isSchoolBased) && !is_null($stepId)) {
			$WorkflowStepsRoles = TableRegistry::get('Workflow.WorkflowStepsRoles');
            $stepRoles = $WorkflowStepsRoles->getRolesByStep($stepId);
            Log::write('debug', 'Roles By Step:');
            Log::write('debug', $stepRoles);

            if (!empty($stepRoles)) {
            	$Areas = TableRegistry::get('Area.Areas');
                $Institutions = TableRegistry::get('Institution.Institutions');
                $Staff = TableRegistry::get('Institution.Staff');

                if ($isSchoolBased) {
                	if (!is_null($institutionId)) {
                		$institutionObj = $Institutions->find()->where([$Institutions->aliasField('id') => $institutionId])->contain(['Areas'])->first();
                        $securityGroupId = $institutionObj->security_group_id;
                        $areaObj = $institutionObj->area;

                        Log::write('debug', 'Institution Id: ' . $institutionId);
                        Log::write('debug', 'Security Group Id: ' . $securityGroupId);
                        Log::write('debug', 'Institution Area:');
                        Log::write('debug', $areaObj);

                        // School based assignee
                        $where = [
                            $this->aliasField('security_group_id') => $securityGroupId,
                            $this->aliasField('security_role_id IN ') => $stepRoles
                        ];
                        $schoolBasedAssigneeQuery = $this
                            ->find('userList', ['where' => $where])
                            ->order([$this->aliasField('created') => 'asc']);

                        Log::write('debug', 'School based assignee query:');
                        Log::write('debug', $schoolBasedAssigneeQuery->sql());

                        $schoolBasedAssigneeOptions = $schoolBasedAssigneeQuery->toArray();
                        Log::write('debug', 'School based assignee:');
                        Log::write('debug', $schoolBasedAssigneeOptions);

                        if (!empty($schoolBasedAssigneeOptions)) {
                        	return key($schoolBasedAssigneeOptions);
                        }
                        $schoolBasedAssigneeOptions = [];
                        // End

                        // Region based assignee
                        $where = [$this->aliasField('security_role_id IN ') => $stepRoles];
                        $regionBasedAssigneeQuery = $this
                            ->find('userList', ['where' => $where, 'area' => $areaObj]);

                        Log::write('debug', 'Region based assignee query:');
                        Log::write('debug', $regionBasedAssigneeQuery->sql());

                        $regionBasedAssigneeOptions = $regionBasedAssigneeQuery->toArray();
                        Log::write('debug', 'Region based assignee:');
                        Log::write('debug', $regionBasedAssigneeOptions);
                        // End

                        $assigneeOptions = $schoolBasedAssigneeOptions + $regionBasedAssigneeOptions;
                	} else {
                		Log::write('debug', 'Institution Id not found.');
                	}
            	} else {
            		$where = [$this->aliasField('security_role_id IN ') => $stepRoles];
                    $assigneeQuery = $this
                        ->find('userList', ['where' => $where]);

                    Log::write('debug', 'Non-School based assignee query:');
                    Log::write('debug', $assigneeQuery->sql());

                    $assigneeOptions = $assigneeQuery->toArray();
            	}

            	// return the first user from the asignee list
            	if (!empty($assigneeOptions)) {
					$assigneeId = key($assigneeOptions);
                }
            } else {
            	Log::write('debug', 'Roles By Step is empty:');
            	Log::write('debug', 'Category: ' . $category);
            	Log::write('debug', 'Creator Id: ' . $createdUserId);

            	// Set assignee as creator only when no roles is configured in workflow step and category of the workflow step is To Do
            	if (!is_null($category) && $category == self::TO_DO) {
            		$assigneeId = $createdUserId;
            	}
            }
		}

		return $assigneeId;
	}
}
