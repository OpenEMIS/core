<?php
namespace Security\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Database\Expression\QueryExpression;

use App\Model\Table\ControllerActionTable;

class SecurityRolesTable extends ControllerActionTable
{
    const FIXED_SYSTEM_GROUP_ID = -1;  // fixed system defined roles
    const CUSTOM_SYSTEM_GROUP_ID = 0;  // custom system defined roles

    private $types = ['user', 'system'];

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
            'Permissions' => ['view', 'edit']
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('name', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table'
            ]);

        return $validator;
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

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $permissionBtn = ['permissions' => $buttons['view']];
        $permissionBtn['permissions']['url']['action'] = 'Permissions';
        $permissionBtn['permissions']['url'][0] = 'index';
        $permissionBtn['permissions']['label'] = '<i class="fa fa-key"></i>' . __('Permissions');

        $buttons = array_merge($permissionBtn, $buttons);

        $groupId = $entity->security_group_id;
        // -1 = system roles, we are not allowing users to modify system roles
        // removing all buttons from the menu
        if ($groupId == self::FIXED_SYSTEM_GROUP_ID) {
            if (array_key_exists('remove', $buttons)) {
                unset($buttons['remove']);
            }
        }

        return $buttons;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if (!$this->AccessControl->check(['Securities', 'UserRoles', 'view'])) {
            unset($this->types[0]);
        } else if (!$this->AccessControl->check(['Securities', 'SystemRoles', 'view'])) {
            unset($this->types[1]);
        }

        $types = $this->types;
        $selectedAction = !is_null($this->request->query('type')) ? $this->request->query('type') : current($types);
        $extra['selectedAction'] = $selectedAction;

        switch ($selectedAction) {
            case 'user':
                $groupOptions = $this->getGroupOptions();
                $selectedGroup = !is_null($this->request->query('security_group_id')) ? $this->request->query('security_group_id') : key($groupOptions);

                $extra['groupOptions'] = $groupOptions;
                $extra['selectedGroup'] = $selectedGroup;

                if ($this->behaviors()->has('Reorder')) {
                    $this->behaviors()->get('Reorder')->config([
                        'filterValues' => [$selectedGroup]
                    ]);
                }
                break;

            case 'system':
                if ($this->behaviors()->has('Reorder')) {
                    $this->behaviors()->get('Reorder')->config([
                        'filterValues' => [self::FIXED_SYSTEM_GROUP_ID, self::CUSTOM_SYSTEM_GROUP_ID]
                    ]);
                }
                break;

            default:
                break;
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        // tabElements
        $controller = $this->controller;
        $types = $this->types;

        // check for roles privileges
        $tabElements = [];
        if ($this->AccessControl->check(['Securities', 'UserRoles', 'view'])) {
            $tabElements['user'] = [
                'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => 'Roles', 'type' => 'user'],
                'text' => $this->getMessage($this->aliasField('userRoles'))
            ];
        }

        if ($this->AccessControl->check(['Securities', 'SystemRoles', 'view'])) {
            $tabElements['system'] = [
                'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => 'Roles', 'type' => 'system'],
                'text' => $this->getMessage($this->aliasField('systemRoles'))
            ];
        }

        $selectedAction = $extra['selectedAction'];

        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $selectedAction);
        // end

        // Add controls filter to index page
        if ($selectedAction == 'user') {
            $groupOptions = $extra['groupOptions'];
            $selectedGroup = $extra['selectedGroup'];

            $extra['elements']['control'] = [
                'name' => 'Security.Roles/controls',
                'data' => [
                    'groupOptions' => $groupOptions,
                    'selectedGroup' => $selectedGroup
                ],
                'order' => 1
            ];
        }
        // end

        $this->field('code', [
            'visible' => false
        ]);
        $this->field('security_group_id', [
            'visible' => false
        ]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $selectedAction = $extra['selectedAction'];

        $userId = $this->Auth->user('id');
        $isSuperAdmin = $this->Auth->user('super_admin');
        $GroupRoles = TableRegistry::get('Security.SecurityGroupUsers');

        switch ($selectedAction) {
            case 'user':
                $selectedGroup = $extra['selectedGroup'];

                $conditions = [$this->aliasField('security_group_id') => $selectedGroup];

                if (!$isSuperAdmin) {
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
                break;

            case 'system':
                $query
                    ->where([$this->aliasField('security_group_id') => self::CUSTOM_SYSTEM_GROUP_ID]) // custom system defined roles
                    ->orWhere([$this->aliasField('security_group_id') => self::FIXED_SYSTEM_GROUP_ID]); // fixed system defined roles

                if (!$isSuperAdmin) {
                    $userRole = $GroupRoles
                        ->find()
                        ->contain('SecurityRoles')
                        ->order(['SecurityRoles.order'])
                        ->where([
                            $GroupRoles->aliasField('security_user_id') => $userId,
                            'SecurityRoles.security_group_id IN ' => [self::FIXED_SYSTEM_GROUP_ID, self::CUSTOM_SYSTEM_GROUP_ID]
                        ])
                        ->first();

                    $query->andWhere([$this->aliasField('order').' > ' => $userRole['security_role']['order']]);
                }
                break;

            default:
                break;
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->has('security_group_id') && $entity->security_group_id == self::FIXED_SYSTEM_GROUP_ID) {
            $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
            unset($toolbarButtonsArray['remove']);
            $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        }

        $this->setupFields($entity, $extra);
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        if ($this->behaviors()->has('Reorder')) {
            if (isset($requestData[$this->alias()]['security_group_id'])) {
                $this->behaviors()->get('Reorder')->config([
                    'filterValues' => [$requestData[$this->alias()]['security_group_id']]
                ]);
            }
        }
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity, $extra);
    }

    private function setupFields(Entity $entity, ArrayObject $extra)
    {
        $this->field('code', [
            'type' => 'hidden'
        ]);
        $this->field('name', [
            'entity' => $entity
        ]);
        $this->field('security_group_id', [
            'entity' => $entity
        ]);

        $this->setFieldOrder([
            'name', 'code', 'order', 'visible', 'security_group_id'
        ]);
    }

    public function onGetName(Event $event, Entity $entity)
    {
        // Transalation is only for security roles
        return ($entity->security_group_id == self::FIXED_SYSTEM_GROUP_ID) ? __($entity->name) : $entity->name;
    }

    public function onUpdateFieldName(Event $event, array $attr, $action, Request $request)
    {
        $types = $this->types;
        $selectedAction = !is_null($this->request->query('type')) ? $this->request->query('type') : current($types);

        switch ($selectedAction) {
            case 'user':
                // no logic
                break;

            case 'system':
                if ($action == 'edit') {
                    $entity = $attr['entity'];
                    
                    if ($entity->has('security_group_id') && $entity->security_group_id == self::FIXED_SYSTEM_GROUP_ID) {
                        $attr['type'] = 'readonly';
                        $attr['value'] = $entity->name;
                        $attr['attr']['value'] = $entity->name;
                    }
                }
                break;

            default:
                break;
        }

        return $attr;
    }

    public function onUpdateFieldSecurityGroupId(Event $event, array $attr, $action, Request $request)
    {
        $types = $this->types;
        $selectedAction = !is_null($this->request->query('type')) ? $this->request->query('type') : current($types);

        switch ($selectedAction) {
            case 'user':
                if ($action == 'add') {
                    $groupOptions = $this->getGroupOptions();

                    $attr['options'] = $groupOptions;
                } elseif ($action == 'edit') {
                    $entity = $attr['entity'];
                    $groupOptions = $this->getGroupOptions();
                    $groupId = $entity->security_group_id;

                    $attr['type'] = 'readonly';
                    $attr['value'] = $groupId;
                    $attr['attr']['value'] = $groupOptions[$groupId];
                }

                break;

            case 'system':
                if ($action == 'view') {
                    $attr['visible'] = false;
                } elseif ($action == 'add') {
                    $attr['type'] = 'hidden';
                    $attr['value'] = self::CUSTOM_SYSTEM_GROUP_ID;
                } elseif ($action == 'edit') {
                    $entity = $attr['entity'];

                    $attr['type'] = 'hidden';
                    $attr['value'] = $entity->security_group_id;
                }
                break;

            default:
                break;
        }

        return $attr;
    }

    public function findByInstitution(Query $query, $options)
    {
        $ids = [self::FIXED_SYSTEM_GROUP_ID, self::CUSTOM_SYSTEM_GROUP_ID];
        if (array_key_exists('id', $options)) {
            // need to get the security_group_id of the institution
            $Institution = TableRegistry::get('Institution.Institutions');
            $institutionQuery = $Institution->find()
                ->where([$Institution->aliasField($Institution->primaryKey()) => $options['id']])
                ->first();
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
        $systemRoleGroupIds = [self::FIXED_SYSTEM_GROUP_ID, self::CUSTOM_SYSTEM_GROUP_ID];
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

    public function getGroupOptions()
    {
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
        $SecurityGroupsTable = $this->SecurityGroups;

        $subquery = $InstitutionsTable->find()
            ->select([$InstitutionsTable->aliasField('id')])
            ->where(function (QueryExpression $exp, Query $q) use ($InstitutionsTable, $SecurityGroupsTable) {
                return $exp->equalFields($InstitutionsTable->aliasField('security_group_id'), $SecurityGroupsTable->aliasField('id'));
            });

        $query = $SecurityGroupsTable->find('list')
            ->where(function (QueryExpression $exp, Query $q) use ($subquery) {
                return $exp->notExists($subquery);
            });

        $userId = $this->Auth->user('id');
        $isSuperAdmin = $this->Auth->user('super_admin');
        if (!$isSuperAdmin) {
            $query->andWhere([
                $SecurityGroupsTable->aliasField('created_user_id = ') => $userId
            ]);
        }

        /* Generated SQL: */

        // is Super Admin
        // SELECT `SecurityGroups`.`id` AS `SecurityGroups__id`, `SecurityGroups`.`name` AS `SecurityGroups__name`
        // FROM `security_groups` `SecurityGroups`
        // WHERE NOT EXISTS (SELECT `Institutions`.`id` AS `Institutions__id` FROM `institutions` `Institutions` WHERE `Institutions`.`security_group_id` = (`SecurityGroups`.`id`));

        // not Super Admin
        // SELECT `SecurityGroups`.`id` AS `SecurityGroups__id`, `SecurityGroups`.`name` AS `SecurityGroups__name`
        // FROM `security_groups` `SecurityGroups`
        // WHERE (NOT EXISTS (SELECT `Institutions`.`id` AS `Institutions__id` FROM `institutions` `Institutions` WHERE `Institutions`.`security_group_id` = (`SecurityGroups`.`id`)) AND `SecurityGroups`.`created_user_id` = :c0);

        $list = $query->toArray();

        return $list;
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

    public function getDeputyPrincipalRoleId()
    {
        $deputyPrincipalData = $this->find()
            ->select([$this->primaryKey()])
            ->where([$this->aliasField('code') => 'DEPUTY_PRINCIPAL'])
            ->first();

        return (!empty($deputyPrincipalData))? $deputyPrincipalData->id: null;
    }
}
