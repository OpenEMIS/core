<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;

class SecurityRolesTable extends AppTable
{
    use MessagesTrait;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('SecurityGroups', ['className' => 'Security.UserGroups']);

        $this->belongsToMany('SecurityFunctions', [
            'className' => 'Security.SecurityFunctions',
            'through' => 'Security.SecurityRoleFunctions',
            'saveStrategy' => 'append'
        ]);

        $this->belongsToMany('GroupUsers', [
            'className' => 'Security.UserGroups',
            'joinTable' => 'security_group_users',
            'foreignKey' => 'security_role_id',
            'targetForeignKey' => 'security_group_id',
            'through' => 'Security.SecurityGroupUsers',
            'dependent' => true
        ]);

        $this->belongsToMany('AlertRules', [
            'className' => 'Alert.AlertRules',
            'joinTable' => 'alerts_roles',
            'foreignKey' => 'security_role_id',
            'targetForeignKey' => 'alert_rule_id',
            'through' => 'Alert.AlertsRoles',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        if ($this->behaviors()->has('Reorder')) {
            $this->behaviors()->get('Reorder')->config([
                    'filter' => 'security_group_id'
                ]);
        }

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Permissions' => ['edit']
        ]);
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        if ($data->offSetExists('security_functions') && $this->urlsafeB64Decode($data['security_functions'])) {
            $data['security_functions'] = json_decode($this->urlsafeB64Decode($data['security_functions']), true);
            foreach ($data['security_functions'] as &$function) {
                foreach ($function['_joinData'] as &$var) {
                    if (is_null($var)) {
                        $var = 0;
                    }
                }
            }
        }
    }

    public function validationDefault(Validator $validator)
    {
        $validator
            ->add('name', 'ruleUnique', [
                    'rule' => 'validateUnique',
                    'provider' => 'table'
                ])
            ;
        return $validator;
    }

    public function beforeAction(Event $event)
    {
        $controller = $this->controller;
        $types = ['user', 'system'];

        $tabElements = [
            'user' => [
                'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => 'Roles', 'type' => 'user'],
                'text' => $this->getMessage($this->aliasField('userRoles'))
            ],
            'system' => [
                'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => 'Roles', 'type' => 'system'],
                'text' => $this->getMessage($this->aliasField('systemRoles'))
            ]
        ];

        // check for roles privileges
        if (!$this->AccessControl->check(['Securities', 'UserRoles', 'view'])) {
            unset($tabElements['user']);
            unset($types[0]);
        } else if (!$this->AccessControl->check(['Securities', 'SystemRoles', 'view'])) {
            unset($tabElements['system']);
            unset($types[1]);
        }

        $selectedAction = $this->request->query('type');
        if (empty($selectedAction) || !in_array($selectedAction, $types)) {
            $selectedAction = current($types);
        }

        $this->request->query['type'] = $selectedAction;
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $selectedAction);

        $this->ControllerAction->field('security_group_id', ['viewType' => $selectedAction]);
        $this->ControllerAction->field('code', ['visible' => false]);

        $action = $this->ControllerAction->action();
        if ($action == 'index' && $selectedAction == 'user') {
            $toolbarElements = [
                ['name' => 'Security.Roles/controls', 'data' => [], 'options' => []]
            ];
            $this->controller->set('toolbarElements', $toolbarElements);
        } else if ($action == 'edit' && $selectedAction == 'system') { //POCOR-2570
            $this->ControllerAction->field('name', ['type' => 'readonly']);
        } else if ($selectedAction == 'user') {
            // for all other actions for user group
            $securityGroupId = $this->request->query('security_group_id');
            if ($this->behaviors()->has('Reorder')) {
                $this->behaviors()->get('Reorder')->config([
                        'filterValues' => [$securityGroupId]
                    ]);
            }
        } else {
            if ($this->behaviors()->has('Reorder')) {
                $this->behaviors()->get('Reorder')->config([
                        'filterValues' => [-1, 0]
                    ]);
            }
        }
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if ($this->behaviors()->has('Reorder')) {
            if (isset($data[$this->alias()]['security_group_id'])) {
                $this->behaviors()->get('Reorder')->config([
                    'filterValues' => [$data[$this->alias()]['security_group_id']]
                ]);
            }
        }
    }

    public function onInitializeButtons(Event $event, ArrayObject $buttons, $action, $isFromModel, ArrayObject $extra)
    {
        // to handle buttons visibility on a different set of permissions
        $selectedAction = $this->request->query('type');
        if (!empty($selectedAction)) {
            $actions = ['user' => 'UserRoles', 'system' => 'SystemRoles'];

            $permissions = ['add', 'edit', 'remove'];
            foreach ($permissions as $permission) {
                if (!$this->AccessControl->check(['Securities', $actions[$selectedAction], $permission])) {
                    unset($buttons[$permission]);
                }
            }
        }
        parent::onInitializeButtons($event, $buttons, $action, $isFromModel, $extra);
    }

    public function onUpdateFieldSecurityGroupId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'index') {
            $attr['visible'] = false;
        }

        $viewType = $attr['viewType'];
        if ($viewType == 'user') {
            $InstitutionsTable = TableRegistry::get('Institution.Institutions');
            $institutionSecurityGroup = $InstitutionsTable->find('list');

            $whereClause = [];

            $SecurityGroupsTable = $this->SecurityGroups;

            if ($this->Auth->user('super_admin') != 1) { //if not admin, then list out SecurityGroups which member created
                $whereClause[] = $SecurityGroupsTable->aliasField('created_user_id') . ' = ' . $this->Auth->user('id');
                $whereClause[] = 'NOT EXISTS ('.$institutionSecurityGroup->sql().' WHERE '.$InstitutionsTable->aliasField('security_group_id').' = '.$SecurityGroupsTable->aliasField('id').')';
            } else { //if admin then show all SecurityGroups excluding default institution System Group
                $whereClause[] = 'NOT EXISTS ('.$institutionSecurityGroup->sql().' WHERE '.$InstitutionsTable->aliasField('security_group_id').' = '.$SecurityGroupsTable->aliasField('id').')';
            }

            if ($action=='edit') { //if edit action select only the saved value
                $whereClause[] = $SecurityGroupsTable->aliasField('id').' = '.$request->query('security_group_id');
            }

            $groupOptions = $SecurityGroupsTable->find('list')
                ->where($whereClause)
                ->toArray();

            //this is for showing the security group dropdown on index page
            $selectedGroup = $this->queryString('security_group_id', $groupOptions);
            $this->advancedSelectOptions($groupOptions, $selectedGroup);
            $request->query['security_group_id'] = $selectedGroup;
            $this->controller->set('groupOptions', $groupOptions);

            if ($action=='edit') {
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $groupOptions[$request->query('security_group_id')]['text'];
            } else {
                $attr['options'] = $groupOptions;
            }
        } else {
            $attr['type'] = 'hidden';
            $attr['value'] = 0;
        }

        return $attr;
    }

    public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options)
    {
        $type = $request->query('type');
        $user = $this->Auth->user();
        $userId = $user['id'];
        if ($user['super_admin'] == 1) { // super admin will show all roles
            $userId = null;
        }
        $count = 0;
        $GroupRoles = TableRegistry::get('Security.SecurityGroupUsers');
        $selectedGroup = $request->query('security_group_id');
        if ($type == 'system') {
            $query
                ->where([$this->aliasField('security_group_id') => 0]) // custom system defined roles
                ->orWhere([$this->aliasField('security_group_id') => -1]); // fixed system defined roles
            if (!is_null($userId)) {
                $userRole = $GroupRoles
                ->find()
                ->contain('SecurityRoles')
                ->order(['SecurityRoles.order'])
                ->where([
                    $GroupRoles->aliasField('security_user_id') => $userId,
                    'SecurityRoles.security_group_id IN ' => [-1,0]
                ])
                ->first();
                $query->andWhere([$this->aliasField('order').' > ' => $userRole['security_role']['order']]);
            }
        } else {
            $conditions = [$this->aliasField('security_group_id') => $selectedGroup];

            if (!is_null($userId)) {
                $userRole = $GroupRoles
                ->find()
                ->contain('SecurityRoles')
                ->order(['SecurityRoles.order'])
                ->where([
                    $GroupRoles->aliasField('security_user_id') => $userId,
                    'SecurityRoles.security_group_id' => $selectedGroup
                ])
                ->first();

                $conditions = [
                    'OR' => [
                        // show roles that are lower privileges than current user role in selected group
                        [
                            $this->aliasField('security_group_id') => $selectedGroup,
                            $this->aliasField('order').' > ' => $userRole['security_role']['order'],
                        ],
                        // also show roles that are created by current user
                        [
                            $this->aliasField('security_group_id') => $selectedGroup,
                            $this->aliasField('created_user_id') => $userId
                        ]
                    ]
                ];
            }
            $query->where($conditions);
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $attr = ['plugin', 'controller', 'action', 'security_group_id', 0, 1];
        $permissionBtn = ['permissions' => $buttons['view']];
        $permissionBtn['permissions']['url']['action'] = 'Permissions';
        $permissionBtn['permissions']['url'][0] = 'index';
        $permissionBtn['permissions']['label'] = '<i class="fa fa-key"></i>' . __('Permissions');

        // foreach ($permissionBtn['permissions']['url'] as $key => $val) {
        // 	if (!in_array($key, $attr)) {
        // 		unset($permissionBtn['permissions']['url'][$key]);
        // 	}
        // }

        $buttons = array_merge($permissionBtn, $buttons);

        $groupId = $entity->security_group_id;
        // -1 = system roles, we are not allowing users to modify system roles
        // removing all buttons from the menu
        if ($groupId == -1) {
            /* POCOR-2570
			if (array_key_exists('view', $buttons)) {
				unset($buttons['view']);
			}
			if (array_key_exists('edit', $buttons)) {
				unset($buttons['edit']);
			}
			*/
            if (array_key_exists('remove', $buttons)) {
                unset($buttons['remove']);
            }
        }
        return $buttons;
    }

    public function findByInstitution(Query $query, $options)
    {
        $ids = [-1, 0];
        if (array_key_exists('id', $options)) {
            // need to get the security_group_id of the institution
            $Institution = TableRegistry::get('Institution.Institutions');
            $institutionQuery = $Institution->find()
                ->where([$Institution->aliasField($Institution->primaryKey()) => $options['id']])
                ->first()
                ;
            if ($institutionQuery) {
                if (isset($institutionQuery->security_group_id)) {
                    $ids[] = $institutionQuery->security_group_id;
                }
            }
        }

        return $query->where([$this->aliasField('security_group_id').' IN' => $ids]);
    }

    public function getSystemRolesList()
    {
        $systemRoleGroupIds = [-1,0];
        return $this->find('list')
            ->find('visible')
            ->where([
                $this->aliasField('security_group_id') . ' IN ' => $systemRoleGroupIds,
                // to exclude homeroom teacher role from selection as this role will be added to user from institution position is_homeroom = true
                $this->aliasField('code') . ' NOT LIKE ' => 'HOMEROOM_TEACHER'
            ])
            ->order([$this->aliasField('order')])
            ->hydrate(false)
            ->toArray();
    }

    public function getRolesOptions($userId = null, $currentRoles = [])
    {
        $roleOptions = [];
        $systemGroupIds = [-1, 0];

        if (!is_null($userId)) {
            foreach ($currentRoles as $role) {
                $roleInfo = $this->get($role);
                $query = $this->find('list');
                if (in_array($roleInfo->security_group_id, $systemGroupIds)) {
                    // For system roles
                    $query = $query->where([$this->aliasField('security_group_id').' IN ' => $systemGroupIds]);
                } else {
                    // For user roles
                    $query = $query->where([$this->aliasField('security_group_id') => $roleInfo->security_group_id]);
                }

                $list = $query
                    ->where([$this->aliasField('order').' > ' => $roleInfo->order])
                    ->order([$this->aliasField('order')])
                    ->toArray();

                $roleOptions = $roleOptions + $list;
            }
        } else {
            $roleOptions = $this->find('list')
                ->where([$this->aliasField('security_group_id').' IN ' => $systemGroupIds])
                ->order([$this->aliasField('order')])
                ->toArray();
        }

        return $roleOptions;
    }

    // this function will return all roles (system roles & user roles) that has lower
    // privileges than the current role of the user in a specific group
    public function getPrivilegedRoleOptionsByGroup($groupId = null, $userId = null, $createUserGroup = false)
    {
        $roleOptions = [];

        // -1 is system defined roles (not editable)
        // 0 is system defined roles (editable)
        // >1 is user defined roles in specific group
        $groupIds = [-1, 0];

        if (!is_null($userId)) { // userId will be null if he/she is a super admin

            $userRoleOptions = [];
            $systemRoleOptions = [];

            $GroupRoles = TableRegistry::get('Security.SecurityGroupUsers');

            if (!$createUserGroup) {
                // Get the highest system role
                $highestSystemRole = $this->find()
                ->matching('GroupUsers')
                ->where([
                    'SecurityGroupUsers.security_user_id' => $userId,
                    $this->aliasField('security_group_id') . ' IN ' => $groupIds,
                    'SecurityGroupUsers.security_group_id' => $groupId
                ])
                ->order([$this->aliasField('order')])
                ->first();
            } else {
                // If the user is a restricted user and is creating a user group
                $highestSystemRole = $this->find()->where([$this->aliasField('name') => 'Group Administrator'])->first();
            }


            // If the user has a system role, then populate the system role options
            // find the list of roles with lower privilege than the current highest privilege role assigned to this user
            if (!empty($highestSystemRole)) {
                $systemRoleOptions = $this
                    ->find('list')
                    ->find('visible')
                    ->where([
                        $this->aliasField('security_group_id'). ' IN ' => $groupIds,
                        $this->aliasField('order') . ' > ' => $highestSystemRole['order'], // the greater the order value, the lower privilege the role is
                    ])
                    ->order([$this->aliasField('order')])
                    ->toArray();

                // If the user has system role and has access to add role, then the user will be able to see the user group roles also,
                // however, if the users they have their own user group role, then they can only see their own user group role below their own, this is overwritten in the code below
                $userRoleOptions = $this
                    ->find('list')
                    ->find('visible')
                    ->where([
                        $this->aliasField('security_group_id') => $groupId,
                    ])
                    ->order([$this->aliasField('order')])
                    ->toArray();
            }

            // this will show only roles of the user in the specified group ($groupId)
            $highestUserRole = $GroupRoles
                ->find()
                ->contain(['SecurityRoles'=> function ($q) {
                            return $q->where(['SecurityRoles.security_group_id NOT IN ' => [-1, 0]]);
                }])
                ->order(['SecurityRoles.order']) // if user is assigned more than one role, therefore the ordering is necessary
                ->where([
                    $GroupRoles->aliasField('security_group_id') => $groupId,
                    $GroupRoles->aliasField('security_user_id') => $userId
                ])
                ->first();

            // If the user has a user role, then populate the user role options
            // find the list of roles with lower privilege than the current highest privilege role assigned to this user
            if (!empty($highestUserRole['security_role'])) {
                $userRoleOptions = $this
                    ->find('list')
                    ->find('visible')
                    ->where([
                        $this->aliasField('security_group_id') => $groupId,
                        $this->aliasField('order') . ' > ' => $highestUserRole['security_role']['order'],
                    ])
                    ->order([$this->aliasField('order')])
                    ->toArray();
            }
            // Merge the permission of the user's system role and user role
            $roleOptions = $systemRoleOptions + $userRoleOptions;
        } else { // super admin will show all roles of system and group specific

            // adding the user role group in
            if (!is_null($groupId)) {
                array_push($groupIds, $groupId);
            }

            $roleOptions = $this
                ->find('list')
                ->find('visible')
                ->where([$this->aliasField('security_group_id') . ' IN ' => $groupIds])
                ->order([$this->aliasField('security_group_id'), $this->aliasField('order')])
                ->toArray();
        }
        return $roleOptions;
    }

    public function onGetName(Event $event, Entity $entity)
    {
        //Transalation is only for security roles
        return ($entity->security_group_id == -1) ? __($entity->name) : $entity->name;
    }

    public function getGroupAdministratorEntity()
    {
        return $this->find()
            ->where([
                $this->aliasField('name') => 'Group Administrator'
            ])
            ->first();
    }

    public function getHomeroomRoleId()
    {
        $homeroomData = $this->find()
            ->select([$this->primaryKey()])
            ->where([$this->aliasField('code') => 'HOMEROOM_TEACHER'])
            ->first();

        return (!empty($homeroomData))? $homeroomData->id: null;
    }

    public function getPrincipalRoleId()
    {
        $principalData = $this->find()
            ->select([$this->primaryKey()])
            ->where([$this->aliasField('code') => 'PRINCIPAL'])
            ->first();

        return (!empty($principalData))? $principalData->id: null;
    }
}
