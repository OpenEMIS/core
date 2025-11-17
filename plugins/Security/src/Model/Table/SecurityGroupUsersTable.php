<?php

namespace Security\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use App\Model\Table\AppTable;
use Cake\Event\EventInterface;
use Cake\Log\Log;

class SecurityGroupUsersTable extends AppTable {

    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    public function initialize(array $config): void {
        parent::initialize($config);
        $this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);
        $this->belongsTo('SecurityGroups', ['className' => 'Security.UserGroups']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Results' => ['index', 'view']
        ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Restful.Model.isAuthorized'] = ['callable' => 'isAuthorized', 'priority' => 1];
        return $events;
    }

    public function isAuthorized(EventInterface $event, $scope, $action, $extra)
    {
        if ($action == 'index' || $action == 'view') {
            // check for the user permission to view here
            $event->stopPropagation();
            return true;
        }
    }

    public function findAllSecurityGroupUsers(Query $query, array $options)
    {
        $SecurityInstitutions = TableRegistry::getTableLocator()->get('Security.SecurityGroupInstitutions');
        $security_user_id = $options['_controller']->request->query['security_user_id'];
        $query
        ->select([
            'security_role_id',
            'institution_id' => $SecurityInstitutions->aliasField('institution_id')
        ])
        ->leftJoin([$SecurityInstitutions->getAlias() => $SecurityInstitutions->getTable()], [
            $SecurityInstitutions->aliasField('security_group_id = ') . $this->aliasField('security_group_id'),
        ])
        ->where(['security_user_id IS' => $security_user_id]);
        return $query;
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options) {
        // only update workflow assignee if the user is added to the group or the role of the user has changed
        if ($entity->isNew()) {
            $model = 0;
            $id = 0;
            $statusId = 0;
            $groupId = $entity->security_group_id;
            $userId = 0;

            if ($entity->has('updateWorkflowAssignee') && $entity->updateWorkflowAssignee == false) {
                // don't trigger shell
            } else {
                $this->triggerUpdateAssigneeShell($model, $id, $statusId, $groupId, $userId);
            }
        } else if ($entity->dirty('security_role_id')) {
            $model = 0;
            $id = 0;
            $statusId = 0;
            $groupId = $entity->security_group_id;
            $userId = $entity->security_user_id;

            $this->triggerUpdateAssigneeShell($model, $id, $statusId, $groupId, $userId);
        }
    }

    public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $options) {
        $model = 0;
        $id = 0;
        $statusId = 0;
        $groupId = 0;
        $userId = $entity->security_user_id;

        if ($entity->has('updateWorkflowAssignee') && $entity->updateWorkflowAssignee == false) {
            // don't trigger shell
        } else {
            $this->triggerUpdateAssigneeShell($model, $id, $statusId, $groupId, $userId);
        }
    }

    private function triggerUpdateAssigneeShell($registryAlias, $id = null, $statusId = null, $groupId = null, $userId = null, $roleId = null) {
        $args = '';
        $args .= !is_null($id) ? ' ' . $id : '';
        $args .= !is_null($statusId) ? ' ' . $statusId : '';
        $args .= !is_null($groupId) ? ' ' . $groupId : '';
        $args .= !is_null($userId) ? ' ' . $userId : '';
        $args .= !is_null($roleId) ? ' ' . $roleId : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake UpdateAssignee ' . $registryAlias . $args;
        $logs = ROOT . DS . 'logs' . DS . 'UpdateAssignee.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;

        try {
            $pid = exec($shellCmd);
            Log::write('debug', $shellCmd);
        } catch (\Exception $ex) {
            Log::write('error', __METHOD__ . ' exception when update assignee : ' . $ex);
        }
    }

    public function insertSecurityRoleForInstitution($data) {
        $institutionId = (isset($data['institution_id'])) ? $data['institution_id'] : null;
        $securityUserId = (isset($data['security_user_id'])) ? $data['security_user_id'] : null;
        $securityRoleId = (isset($data['security_role_id'])) ? $data['security_role_id'] : null;

        if (!is_null($institutionId) && !is_null($securityUserId) && !is_null($securityRoleId)) {
            $Institution = TableRegistry::getTableLocator()->get('Institution.Institutions');
            $institutionQuery = $Institution
                    ->find()
                    ->where([$Institution->aliasField($Institution->getPrimaryKey()) => $institutionId])
                    ->first()
            ;

            if ($institutionQuery) {
                $securityGroupId = (isset($institutionQuery->security_group_id)) ? $institutionQuery->security_group_id : null;
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
                        ['SecurityRoleFunctions' => 'security_role_functions'], [
                    'SecurityRoleFunctions.security_role_id = ' . $this->aliasField('security_role_id'),
                    'SecurityRoleFunctions.security_function_id' => $securityFunctionId,
                    'SecurityRoleFunctions.' . $field => 1
                        ]
                )
                ->where([$this->aliasField('security_user_id') => $userId, $this->aliasField('security_group_id') => $securityGroupId])
                ->disableHydration() // POCOR-8533
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
                        $this->aliasField('security_group_id') . ' IN ' => $groupIds
                    ])
                    ->order('SecurityRoles.order')
                    ->group([$this->aliasField('security_role_id')])
                    ->select([$this->aliasField('security_role_id')])
                    ->EnableHydration(false)
                    ->toArray();
            return $securityRoles;
        } else {
            return [];
        }
    }

    public function getInstitutionsByUser($userId = 0) {

        $groupIds = $this
                ->find('list', ['keyField' => 'id', 'valueField' => 'security_group_id'])
                ->where([$this->aliasField('security_user_id') => $userId])
                ->toArray();

        if (!empty($groupIds)) {
            $SecurityGroupInstitutions = TableRegistry::getTableLocator()->get('Security.SecurityGroupInstitutions');
            $institutionIds = $SecurityGroupInstitutions
                    ->find('list', ['keyField' => 'institution_id', 'valueField' => 'institution_id'])
                    ->where([$SecurityGroupInstitutions->aliasField('security_group_id') . ' IN ' => $groupIds])
                    ->toArray();

            $SecurityGroupAreas = TableRegistry::getTableLocator()->get('Security.SecurityGroupAreas');
            $areaInstitutions = $SecurityGroupAreas
                    ->find('list', ['keyField' => 'Institutions.id', 'valueField' => 'Institutions.id'])
                    ->select(['Institutions.id'])
                    ->innerJoin(['AreaAll' => 'areas'], ['AreaAll.id = SecurityGroupAreas.area_id'])
                    ->innerJoin(['Areas' => 'areas'], [
                        'Areas.lft >= AreaAll.lft',
                        'Areas.rght <= AreaAll.rght'
                    ])
                    ->innerJoin(['Institutions' => 'institutions'], ['Institutions.area_id = Areas.id'])
                    ->where([$SecurityGroupAreas->aliasField('security_group_id') . ' IN ' => $groupIds])
                    ->toArray();

            $institutionIds = $institutionIds + $areaInstitutions;

            return $institutionIds;
        } else {
            return [];
        }
    }

    public function findRoleByInstitution(Query $query, array $options) {
        $userId = $options['security_user_id'];
        $institutionId = $options['institution_id'];
        $query
                ->innerJoin(['SecurityGroupInstitutions' => 'security_group_institutions'], [
                    'SecurityGroupInstitutions.security_group_id = ' . $this->aliasField('security_group_id'),
                    'SecurityGroupInstitutions.institution_id' => $institutionId
                ])
                ->where([$this->aliasField('security_user_id') => $userId])
                ->distinct([$this->aliasField('security_role_id')]);
        return $query;
    }

    public function findUserList(Query $query, array $options) {
        $where = isset($options['where']) ? $options['where'] : [];
        $area = isset($options['area']) ? $options['area'] : null;

        $query->find('all') //POCOR-8808
        ->matching('Users', function ($q) {
            return $q; //Ensures only rows with matching Users are included
        })
        ->find('list', ['keyField' => function ($query) {
                                return $query->user->id;
                            }, 'valueField' => function ($query) {
                                return $query->user ? $query->user->get('name_with_id_role') : '';
                            }])
                /*->select([
                    $this->Users->aliasField('id'),
                    $this->Users->aliasField('openemis_no'),
                    $this->Users->aliasField('first_name'),
                    $this->Users->aliasField('middle_name'),
                    $this->Users->aliasField('third_name'),
                    $this->Users->aliasField('last_name'),
                    $this->Users->aliasField('preferred_name')
                ])*/
                ->contain([$this->Users->getAlias()])
                //POCOR-5688 starts
                ->leftJoin([$this->SecurityRoles->getAlias() => $this->SecurityRoles->getTable()], [
                    $this->SecurityRoles->aliasField('id =') . $this->aliasField('security_role_id')
                ])
                ->order([
                    $this->SecurityRoles->aliasField('order') => 'ASC',
                    $this->aliasField('security_role_id') => 'DESC'
                ])
                //POCOR-5688 ends
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
    public function getAssigneeList($params = []) {
        $isSchoolBased = isset($params['is_school_based']) ? $params['is_school_based'] : null;
        $stepId = isset($params['workflow_step_id']) ? $params['workflow_step_id'] : null;
        $institutionId = isset($params['institution_id']) ? $params['institution_id'] : $params['url_institution_id']; //POCOR-6619
//        Log::write('debug', 'Is School Based: ' . $isSchoolBased);
//        Log::write('debug', 'Workflow Step Id: ' . $stepId);

        $assigneeOptions = [];
        if (!is_null($stepId)) {
            $WorkflowStepsRoles = TableRegistry::getTableLocator()->get('Workflow.WorkflowStepsRoles');
            $stepRoles = $WorkflowStepsRoles->getRolesByStep($stepId);
//            Log::write('debug', 'Roles By Step:');
//            Log::write('debug', $stepRoles);

            if (!empty($stepRoles)) {
                $SecurityGroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
                $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
                $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');

                if ($isSchoolBased) {
                    if (is_null($institutionId)) {
                        Log::write('debug', 'Institution Id not found.');
                    } else {
                        $institutionObj = $Institutions->find()->where([$Institutions->aliasField('id') => $institutionId])->contain(['Areas'])->first();
                        $securityGroupId = $institutionObj->security_group_id;
                        $areaObj = $institutionObj->area;

//                        Log::write('debug', 'Institution Id: ' . $institutionId); // POCOR-8853 removed logging
//                        Log::write('debug', 'Security Group Id: ' . $securityGroupId);
//                        Log::write('debug', 'Institution Area:');
//                        Log::write('debug', print_r($areaObj, true));

                        // School based assignee
                        $where = [
                            'OR' => [[$SecurityGroupUsers->aliasField('security_group_id') => $securityGroupId],
                                    ['Institutions.id' => $institutionId]],
                            $SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles
                        ];
                        $schoolBasedAssigneeQuery = $SecurityGroupUsers
                                ->find('userList', ['where' => $where])
                                ->leftJoinWith('SecurityGroups.Institutions');

//                        Log::write('debug', 'School based assignee query:');
//                        Log::write('debug', (string) $schoolBasedAssigneeQuery->sql());
                        $schoolBasedAssigneeOptions = $schoolBasedAssigneeQuery->toArray();

//                        Log::write('debug', 'School based assignee:');
//                        Log::write('debug', (string) $schoolBasedAssigneeOptions);
                        // End
                        // Region based assignee
                        $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                        $regionBasedAssigneeQuery = $SecurityGroupUsers
                                    ->find('UserList', ['where' => $where, 'area' => $areaObj]);
//                        Log::write('debug', 'Region based assignee query:');
//                        Log::write('debug', (string) $regionBasedAssigneeQuery->sql());

                        $regionBasedAssigneeOptions = $regionBasedAssigneeQuery->toArray();
//                        Log::write('debug', 'Region based assignee:');
//                        Log::write('debug', (string) $regionBasedAssigneeOptions);
                        // End

                        $assigneeOptions = $schoolBasedAssigneeOptions + $regionBasedAssigneeOptions;
                    }
                } else {
                    $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                    $assigneeQuery = $SecurityGroupUsers
                            ->find('userList', ['where' => $where])
                            ->order([$SecurityGroupUsers->aliasField('security_role_id') => 'DESC']);

//                    Log::write('debug', 'Non-School based assignee query:');
//                    Log::write('debug', (string) $assigneeQuery->sql());

                    $assigneeOptions = $assigneeQuery->toArray();
                }
            }
        }

        return $assigneeOptions;
    }

    // IMPORTANT: when editing this method, need to consider impact on getAssigneeList()
    public function getFirstAssignee($params = []) {
        $isSchoolBased = isset($params['is_school_based']) ? $params['is_school_based'] : null;
        $stepId = isset($params['workflow_step_id']) ? $params['workflow_step_id'] : null;
        $institutionId = isset($params['institution_id']) ? $params['institution_id'] : null;
        $category = isset($params['category']) ? $params['category'] : null;
        $createdUserId = isset($params['created_user_id']) ? $params['created_user_id'] : null;

//        Log::write('debug', 'Is School Based: ' . $isSchoolBased);
//        Log::write('debug', 'Step Id: ' . $stepId);

        $assigneeId = 0;
        if (!is_null($isSchoolBased) && !is_null($stepId)) {
            $WorkflowStepsRoles = TableRegistry::getTableLocator()->get('Workflow.WorkflowStepsRoles');
            $stepRoles = $WorkflowStepsRoles->getRolesByStep($stepId);
            //Log::write('debug', 'Roles By Step:');
            //Log::write('debug', $stepRoles);

            if (!empty($stepRoles)) {
                $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
                $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
                $Staff = TableRegistry::getTableLocator()->get('Institution.Staff');

                if ($isSchoolBased) {
                    if (!is_null($institutionId)) {
                        $institutionObj = $Institutions->find()->where([$Institutions->aliasField('id') => $institutionId])->contain(['Areas'])->first();
                        $securityGroupId = $institutionObj->security_group_id;
                        $areaObj = $institutionObj->area;

//                        Log::write('debug', 'Institution Id: ' . $institutionId);
//                        Log::write('debug', 'Security Group Id: ' . $securityGroupId);
//                        Log::write('debug', 'Institution Area:');
//                        Log::write('debug', $areaObj);

                        // School based assignee
                        $where = [
                            $this->aliasField('security_group_id') => $securityGroupId,
                            $this->aliasField('security_role_id IN ') => $stepRoles
                        ];
                        $schoolBasedAssigneeQuery = $this
                                ->find('userList', ['where' => $where])
                                ->order([$this->aliasField('created') => 'asc']);

//                        Log::write('debug', 'School based assignee query:');
//                        Log::write('debug', $schoolBasedAssigneeQuery->sql());

                        $schoolBasedAssigneeOptions = $schoolBasedAssigneeQuery->toArray();
                        /*Log::write('debug', 'School based assignee:');
                        Log::write('debug', $schoolBasedAssigneeOptions);*/

                        if (!empty($schoolBasedAssigneeOptions)) {
                            return key($schoolBasedAssigneeOptions);
                        }
                        $schoolBasedAssigneeOptions = [];
                        // End
                        // Region based assignee
                        $where = [$this->aliasField('security_role_id IN ') => $stepRoles];
                        $regionBasedAssigneeQuery = $this
                                ->find('userList', ['where' => $where, 'area' => $areaObj]);

                        // Log::write('debug', 'Region based assignee query:');
                        // Log::write('debug', $regionBasedAssigneeQuery->sql());

                        $regionBasedAssigneeOptions = $regionBasedAssigneeQuery->toArray();
                        /*Log::write('debug', 'Region based assignee:');
                        Log::write('debug', $regionBasedAssigneeOptions);*/
                        // End

                        $assigneeOptions = $schoolBasedAssigneeOptions + $regionBasedAssigneeOptions;
                    } else {
                        $message = ['Institution Id not found.']; // Example array
                        Log::write('debug', json_encode($message)); // Convert to JSON string

                    }
                } else {
                    $where = [$this->aliasField('security_role_id IN ') => $stepRoles];
                    $assigneeQuery = $this
                            ->find('userList', ['where' => $where]);
                    $assigneeOptions = $assigneeQuery->toArray();
                }

                // return the first user from the asignee list
                if (!empty($assigneeOptions)) {
                    $assigneeId = key($assigneeOptions);
                }
            } else {
                Log::write('debug', 'Roles By Step is empty:');
                Log::write('debug', 'Category: ' . json_encode($category));
                Log::write('debug', 'Creator Id: ' . json_encode($createdUserId));


                // Set assignee as creator only when no roles is configured in workflow step and category of the workflow step is To Do
                $assigneeId = $createdUserId;
            }
        }

        return $assigneeId;
    }

    public function findContactList(Query $query, array $options)
    {
        $conditions = [
            $this->aliasField('security_role_id') => $options['securityRoleId']
        ];

        if (isset($options['institutionId'])) {
            $institutionId = $options['institutionId'];
            $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
            $securityGroupId = $Institutions->get($institutionId)->security_group_id;
            $conditions[$this->aliasField('security_group_id')] = $securityGroupId;
        }

        return $query
            ->matching('Users', function ($q) {
                return $q->where([
                    'OR' => [
                        'Users.email IS NOT NULL',
                        'Users.mobile_number IS NOT NULL'
                    ]
                ]);
            })
            ->where($conditions);
    }

    /*
     * Function Name:getAreaCodesByUser
     * Purpose: Get the AREA CODE assign in User Group
     * Parameter: userId
     * Date: 3 July 2019
     */
    public function getAreaCodesByUser($userId = 0) {
        $areaCodes = [];

        if($userId <= 0){
           return $areaCodes;
        }

        $groupIds = $this
                ->find('list', ['keyField' => 'id', 'valueField' => 'security_group_id'])
                ->where([$this->aliasField('security_user_id') => $userId])
                ->toArray();

        if (!empty($groupIds)) {
            $SecurityGroupAreas = TableRegistry::getTableLocator()->get('Security.SecurityGroupAreas');
            $areaCodes = $SecurityGroupAreas
                    ->find('list', ['keyField' => 'AreaAll.code', 'valueField' => 'AreaAll.code'])
                    ->select(['AreaAll.code'])
                    ->innerJoin(['AreaAll' => 'areas'], ['AreaAll.id = SecurityGroupAreas.area_id'])
                    ->innerJoin(['Areas' => 'areas'], [
                        'Areas.lft >= AreaAll.lft',
                        'Areas.rght <= AreaAll.rght'
                    ])
                    ->where([$SecurityGroupAreas->aliasField('security_group_id') . ' IN ' => $groupIds])
                    ->toArray();
        }

        return $areaCodes;
    }

}
