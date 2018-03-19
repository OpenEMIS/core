<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Datasource\Exception\RecordNotFoundException;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;
use App\Model\Traits\HtmlTrait;
use App\Model\Table\ControllerActionTable;

class UserGroupsTable extends ControllerActionTable
{
    use MessagesTrait;
    use HtmlTrait;

    public function initialize(array $config)
    {
        $this->table('security_groups');
        parent::initialize($config);

        $this->belongsToMany('Users', [
            'className' => 'Security.Users',
            'joinTable' => 'security_group_users',
            'foreignKey' => 'security_group_id',
            'targetForeignKey' => 'security_user_id',
            'through' => 'Security.SecurityGroupUsers',
            'dependent' => true
        ]);

        $this->belongsToMany('Areas', [
            'className' => 'Area.Areas',
            'joinTable' => 'security_group_areas',
            'foreignKey' => 'security_group_id',
            'targetForeignKey' => 'area_id',
            'through' => 'Security.SecurityGroupAreas',
            'dependent' => true
        ]);

        $this->belongsToMany('Institutions', [
            'className' => 'Institution.Institutions',
            'joinTable' => 'security_group_institutions',
            'foreignKey' => 'security_group_id',
            'targetForeignKey' => 'institution_id',
            'through' => 'Security.SecurityGroupInstitutions',
            'dependent' => true
        ]);

        $this->belongsToMany('Roles', [
            'className' => 'Security.SecurityRoles',
            'joinTable' => 'security_group_users',
            'foreignKey' => 'security_group_id',
            'targetForeignKey' => 'security_role_id',
            'through' => 'Security.SecurityGroupUsers',
            'dependent' => true
        ]);

        $this->setDeleteStrategy('restrict');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $newEvent = [
            $events['ControllerAction.Model.ajaxAreaAutocomplete'] = 'ajaxAreaAutocomplete',
            $events['ControllerAction.Model.ajaxInstitutionAutocomplete'] = 'ajaxInstitutionAutocomplete',
            $events['ControllerAction.Model.ajaxUserAutocomplete'] = 'ajaxUserAutocomplete',
            $events['ControllerAction.Model.getAssociatedRecordConditions'] = 'getAssociatedRecordConditions'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onUpdateIncludes(Event $event, ArrayObject $includes, $action)
    {
        if ($action == 'edit') {
            $includes['autocomplete'] = [
                'include' => true,
                'css' => ['OpenEmis.../plugins/autocomplete/css/autocomplete'],
                'js' => ['OpenEmis.../plugins/autocomplete/js/autocomplete']
            ];
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $userId = $this->Auth->user('id');
        $securityGroupId = $entity->id;
        // -1 = system roles, we are not allowing users to modify system roles
        // removing all buttons from the menu
        if (!$this->AccessControl->isAdmin()) {
            $SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
            if (!$SecurityGroupUsersTable->checkEditGroup($userId, $securityGroupId, '_edit')) {
                if (array_key_exists('edit', $buttons)) {
                    unset($buttons['edit']);
                }
            }

            if (!$SecurityGroupUsersTable->checkEditGroup($userId, $securityGroupId, '_delete')) {
                if (array_key_exists('remove', $buttons)) {
                    unset($buttons['remove']);
                }
            }
        }

        return $buttons;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'areas') {
            return __('Areas (Education)');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    
    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->data[$this->alias()]['security_group_id'] = $entity->id;

        if (!$this->AccessControl->isAdmin()) {
            $userId = $this->Auth->user('id');
            $securityGroupId = $this->request->data[$this->alias()]['security_group_id'];
            $SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
            if (!$SecurityGroupUsersTable->checkEditGroup($userId, $securityGroupId, '_edit')) {
                $this->toggle('edit', false);
            }

            if (!$SecurityGroupUsersTable->checkEditGroup($userId, $securityGroupId, '_delete')) {
                $this->toggle('remove', false);
            }
        }
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if (!$this->AccessControl->isAdmin()) {
            $userId = $this->Auth->user('id');
            $securityGroupId = $entity->id;
            $SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
            if (!$SecurityGroupUsersTable->checkEditGroup($userId, $securityGroupId, '_edit')) {
                $urlParams = $this->url('index');
                $event->stopPropagation();
                return $this->controller->redirect($urlParams);
            }
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $controller = $this->controller;
        $tabElements = [
            $this->alias() => [
                'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => $this->alias()],
                'text' => $this->getMessage($this->aliasField('tabTitle'))
            ],
            'SystemGroups' => [
                'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => 'SystemGroups'],
                'text' => $this->getMessage('SystemGroups.tabTitle')
            ]
        ];
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());

        $this->field('areas', [
            'type' => 'area_table',
            'valueClass' => 'table-full-width',
            'visible' => ['index' => false, 'view' => true, 'edit' => true]
        ]);
        $this->field('institutions', [
            'type' => 'institution_table',
            'valueClass' => 'table-full-width',
            'visible' => ['index' => false, 'view' => true, 'edit' => true]
        ]);

        $roleOptions = $this->Roles->find('list')->toArray();
        $this->field('users', [
            'type' => 'user_table',
            'valueClass' => 'table-full-width',
            'roleOptions' => $roleOptions,
            'visible' => ['index' => false, 'view' => true, 'edit' => true]
        ]);

        $this->setFieldOrder([
            'name', 'areas', 'institutions', 'users'
        ]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Areas.AreaLevels', 'Institutions', 'Users', 'Roles']);
    }

    public function onGetAreaTableElement(Event $event, $action, $entity, $attr, $options = [])
    {
        $tableHeaders = [__('Level'), __('Code'), __('Area')];
        $tableCells = [];
        $alias = $this->alias();
        $key = 'areas';

        if ($action == 'index') {
            // not showing
        } else if ($action == 'view') {
            $associated = $entity->extractOriginal([$key]);
            if (!empty($associated[$key])) {
                foreach ($associated[$key] as $i => $obj) {
                    $rowData = [];
                    $rowData[] = [$obj->area_level->name, ['autocomplete-exclude' => $obj->id]];
                    $rowData[] = $obj->code;
                    $rowData[] = $obj->name;
                    $tableCells[] = $rowData;
                }
            }
        } else if ($action == 'edit') {
            $tableHeaders[] = ''; // for delete column
            $Form = $event->subject()->Form;
            $Form->unlockField('area_id');

            if ($this->request->is(['get'])) {
                if (!array_key_exists($alias, $this->request->data)) {
                    $this->request->data[$alias] = [$key => []];
                } else {
                    $this->request->data[$alias][$key] = [];
                }

                $associated = $entity->extractOriginal([$key]);
                if (!empty($associated[$key])) {
                    foreach ($associated[$key] as $i => $obj) {
                        $this->request->data[$alias][$key][] = [
                            'id' => $obj->id,
                            '_joinData' => ['level' => $obj->area_level->name, 'code' => $obj->code, 'area_id' => $obj->id, 'name' => $obj->name]
                        ];
                    }
                }
            }
            // refer to addEditOnAddArea for http post
            if ($this->request->data("$alias.$key")) {
                $associated = $this->request->data("$alias.$key");

                foreach ($associated as $i => $obj) {
                    $joinData = $obj['_joinData'];
                    $rowData = [];
                    $name = $joinData['name'];
                    $name .= $Form->hidden("$alias.$key.$i.id", ['value' => $obj['id']]);
                    $name .= $Form->hidden("$alias.$key.$i._joinData.level", ['value' => $joinData['level']]);
                    $name .= $Form->hidden("$alias.$key.$i._joinData.code", ['value' => $joinData['code']]);
                    $name .= $Form->hidden("$alias.$key.$i._joinData.area_id", ['value' => $joinData['area_id']]);
                    $name .= $Form->hidden("$alias.$key.$i._joinData.name", ['value' => $joinData['name']]);
                    $Form->unlockField("$alias.$key.$i.id");
                    $Form->unlockField("$alias.$key.$i._joinData.level");
                    $Form->unlockField("$alias.$key.$i._joinData.code");
                    $Form->unlockField("$alias.$key.$i._joinData.area_id");
                    $Form->unlockField("$alias.$key.$i._joinData.name");
                    $rowData[] = [$joinData['level'], ['autocomplete-exclude' => $joinData['area_id']]];
                    $rowData[] = $joinData['code'];
                    $rowData[] = $name;
                    $rowData[] = $this->getDeleteButton();
                    $tableCells[] = $rowData;
                }
            }
        }
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->subject()->renderElement('Security.Groups/' . $key, ['attr' => $attr]);
    }

    public function addEditOnAddArea(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $alias = $this->alias();

        if ($data->offsetExists('area_id')) {
            $id = $data['area_id'];
            try {
                $obj = $this->Areas->get($id, ['contain' => 'AreaLevels']);

                if (!array_key_exists('areas', $data[$alias])) {
                    $data[$alias]['areas'] = [];
                }
                $data[$alias]['areas'][] = [
                    'id' => $obj->id,
                    '_joinData' => ['level' => $obj->area_level->name, 'code' => $obj->code, 'area_id' => $obj->id, 'name' => $obj->name]
                ];
            } catch (RecordNotFoundException $ex) {
                $this->log(__METHOD__ . ': Record not found for id: ' . $id, 'debug');
            }
        }
    }

    public function onGetInstitutionTableElement(Event $event, $action, $entity, $attr, $options = [])
    {
        $tableHeaders = [__('Code'), __('Institution')];
        $tableCells = [];
        $alias = $this->alias();
        $key = 'institutions';

        if ($action == 'index') {
            // not showing
        } else if ($action == 'view') {
            $associated = $entity->extractOriginal([$key]);
            if (!empty($associated[$key])) {
                foreach ($associated[$key] as $i => $obj) {
                    $rowData = [];
                    $rowData[] = [$obj->code, ['autocomplete-exclude' => $obj->id]];
                    $rowData[] = $obj->name;
                    $tableCells[] = $rowData;
                }
            }
        } else if ($action == 'edit') {
            $tableHeaders[] = ''; // for delete column
            $Form = $event->subject()->Form;
            $Form->unlockField('institution_id');
            if ($this->request->is(['get'])) {
                if (!array_key_exists($alias, $this->request->data)) {
                    $this->request->data[$alias] = [$key => []];
                } else {
                    $this->request->data[$alias][$key] = [];
                }

                $associated = $entity->extractOriginal([$key]);
                if (!empty($associated[$key])) {
                    foreach ($associated[$key] as $i => $obj) {
                        $this->request->data[$alias][$key][] = [
                            'id' => $obj->id,
                            '_joinData' => ['code' => $obj->code, 'institution_id' => $obj->id, 'name' => $obj->name]
                        ];
                    }
                }
            }
            // refer to addEditOnAddInstitution for http post
            if ($this->request->data("$alias.$key")) {
                $associated = $this->request->data("$alias.$key");

                foreach ($associated as $i => $obj) {
                    $joinData = $obj['_joinData'];
                    $rowData = [];
                    $name = $joinData['name'];
                    $name .= $Form->hidden("$alias.$key.$i.id", ['value' => $joinData['institution_id']]);
                    $name .= $Form->hidden("$alias.$key.$i._joinData.code", ['value' => $joinData['code']]);
                    $name .= $Form->hidden("$alias.$key.$i._joinData.name", ['value' => $joinData['name']]);
                    $name .= $Form->hidden("$alias.$key.$i._joinData.institution_id", ['value' => $joinData['institution_id']]);
                    $Form->unlockField("$alias.$key.$i.id");
                    $Form->unlockField("$alias.$key.$i._joinData.code");
                    $Form->unlockField("$alias.$key.$i._joinData.institution_id");
                    $Form->unlockField("$alias.$key.$i._joinData.name");
                    $rowData[] = [$joinData['code'], ['autocomplete-exclude' => $joinData['institution_id']]];
                    $rowData[] = $name;
                    $rowData[] = $this->getDeleteButton();
                    $tableCells[] = $rowData;
                }
            }
        }
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->subject()->renderElement('Security.Groups/' . $key, ['attr' => $attr]);
    }

    public function addEditOnAddInstitution(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $alias = $this->alias();

        if ($data->offsetExists('institution_id')) {
            $id = $data['institution_id'];
            try {
                $obj = $this->Institutions->get($id);

                if (!array_key_exists('institutions', $data[$alias])) {
                    $data[$alias]['institutions'] = [];
                }
                $data[$alias]['institutions'][] = [
                    'id' => $obj->id,
                    '_joinData' => ['code' => $obj->code, 'institution_id' => $obj->id, 'name' => $obj->name]
                ];
            } catch (RecordNotFoundException $ex) {
                $this->log(__METHOD__ . ': Record not found for id: ' . $id, 'debug');
            }
        }
    }

    public function onGetUserTableElement(Event $event, $action, $entity, $attr, $options = [])
    {
        $tableHeaders = [__('OpenEMIS ID'), __('Name'), __('Role')];
        $tableCells = [];
        $alias = $this->alias();
        $key = 'users';

        if ($action == 'index') {
            // not showing
        } else if ($action == 'view') {
            $roleOptions = $attr['roleOptions'];
            $associated = $entity->extractOriginal([$key]);
            if (!empty($associated[$key])) {
                foreach ($associated[$key] as $i => $obj) {
                    $rowData = [];
                    $rowData[] = $event->subject()->Html->link($obj->openemis_no, [
                        'plugin' => 'Directory',
                        'controller' => 'Directories',
                        'action' => 'Directories',
                        'view',
                        $this->paramsEncode(['id' => $obj->id])
                    ]);
                    $rowData[] = $obj->name;
                    $roleId = $obj->_joinData->security_role_id;

                    if (array_key_exists($roleId, $roleOptions)) {
                        $rowData[] = $roleOptions[$roleId];
                    } else {
                        $this->log(__METHOD__ . ': Orphan record found for role id: ' . $roleId, 'debug');
                        $rowData[] = '';
                    }
                    $tableCells[] = $rowData;
                }
            }
        } else if ($action == 'edit') {
            $tableHeaders[] = ''; // for delete column
            $Form = $event->subject()->Form;
            $HtmlField = $event->subject();
            $Form->unlockField('user_id');
            $user = $this->Auth->user();
            $userId = $user['id'];
            $userIdRoleOrder = '';
            if ($user['super_admin'] == 1) { // super admin will show all roles
                $userId = null;
            }
            $roleOptions = $this->Roles->getPrivilegedRoleOptionsByGroup($entity->id, $userId);
            if ($this->request->is(['get'])) {
                if (!array_key_exists($alias, $this->request->data)) {
                    $this->request->data[$alias] = [$key => []];
                } else {
                    $this->request->data[$alias][$key] = [];
                }

                $associated = $entity->extractOriginal([$key]);
                if (!empty($associated[$key])) {
                    foreach ($associated[$key] as $i => $obj) {
                        $this->request->data[$alias][$key][] = [
                            'id' => $obj->id,
                            '_joinData' => [
                                'openemis_no' => $obj->openemis_no,
                                'security_user_id' => $obj->id,
                                'name' => $obj->name,
                                'security_role_id' => $obj->_joinData->security_role_id,
                                'security_role_id_order' => $entity->roles[$i]->order //adding role order for checking during edit
                            ]
                        ];
                        $entity->users[$i]->security_role_id_order = $entity->roles[$i]->order; //adding role order for checking during edit

                        //keep the current login user role order.
                        if ($userId == $entity->users[$i]->id) {
                            $userIdRoleOrder = $entity->roles[$i]->order;
                        }
                    }
                } else {
                    if (!$this->AccessControl->isAdmin()) {
                        $groupAdmin = $this->Roles->getGroupAdministratorEntity();
                        $UserTable = TableRegistry::get('Preferences');
                        $user = $UserTable->get($userId);
                        if (empty($this->request->data[$alias][$key])) {
                            $this->request->data[$alias][$key][] = [
                                'id' => $userId,
                                '_joinData' => [
                                    'openemis_no' => $user->openemis_no,
                                    'security_user_id' => $userId,
                                    'name' => $user->name,
                                    'security_role_id' => $groupAdmin->id
                                ]
                            ];
                        }
                    }
                }
            }

            $notEditableUsers = [];

            if (!$this->AccessControl->isAdmin()) {
                if ($entity->isNew()) {
                    $roleOptions = $this->Roles->getPrivilegedRoleOptionsByGroup($entity->id, $userId, true);
                }

                //un-editable for the original user and also creator of the group and user with higher or equal role
                $associated = $entity->extractOriginal([$key]);
                // $found = false;
                if (!empty($associated[$key]) && !$entity->isNew()) {
                    foreach ($associated[$key] as $i => $obj) {
                        if ($obj->id == $userId || $obj->id == $entity->created_user_id || (!empty($userIdRoleOrder) && $obj->security_role_id_order <= $userIdRoleOrder)) {
                            $rowData = [];
                            $name = $obj->name;
                            $rowData[] = $obj->openemis_no;
                            $rowData[] = $name;

                            // To revisit this part again due to a bug when user add itself in
                            if (isset($obj->_joinData->security_role_id)) {
                                $securityRoleName = $this->Roles->get($obj->_joinData->security_role_id)->name;
                                $this->Session->write($this->registryAlias().'.security_role_id', $securityRoleName);
                                $rowData[] = $securityRoleName;
                            } else {
                                $securityRoleName = $this->Session->read($this->registryAlias().'.security_role_id');
                                $rowData[] = $securityRoleName;
                            }

                            $notEditableUsers[] = $obj->id;

                            $notEditableVal = '';
                            $notEditableVal .= $Form->hidden("$alias.$key.$i.id", ['value' => $obj->id]);
                            $notEditableVal .= $Form->hidden("$alias.$key.$i._joinData.openemis_no", ['value' => $obj->openemis_no]);
                            $notEditableVal .= $Form->hidden("$alias.$key.$i._joinData.name", ['value' => $obj->name]);
                            $notEditableVal .= $Form->hidden("$alias.$key.$i._joinData.security_user_id", ['value' => $obj->id]);
                            $notEditableVal .= $Form->hidden("$alias.$key.$i._joinData.security_role_id", ['value' => $obj->_joinData->security_role_id]);
                            $notEditableVal .= $Form->hidden("$alias.$key.$i._joinData.security_role_id_order", ['value' => $obj->security_role_id_order]);

                            $rowData[] = $notEditableVal;
                            $tableCells[] = $rowData;
                            // $found = true;
                            // break;
                        }
                    }
                }
            }


            // refer to addEditOnAddUser for http post
            if ($this->request->data("$alias.$key")) {
                $associated = $this->request->data("$alias.$key");
                foreach ($associated as $i => $obj) {
                    $joinData = $obj['_joinData'];
                    //editable only for other than current user, creator of group and user with lower role.
                    if (!in_array($joinData['security_user_id'], $notEditableUsers) && $joinData['security_user_id'] != $userId) {
                        $rowData = [];
                        $name = $joinData['name'];
                        $name .= $Form->hidden("$alias.$key.$i.id", ['value' => $joinData['security_user_id']]);
                        $name .= $Form->hidden("$alias.$key.$i._joinData.openemis_no", ['value' => $joinData['openemis_no']]);
                        $name .= $Form->hidden("$alias.$key.$i._joinData.name", ['value' => $joinData['name']]);
                        $name .= $Form->hidden("$alias.$key.$i._joinData.security_user_id", ['value' => $joinData['security_user_id']]);
                        $name .= $Form->hidden("$alias.$key.$i._joinData.security_role_id_order", ['value' => $joinData['security_role_id_order']]);
                        $Form->unlockField("$alias.$key.$i.id");
                        $Form->unlockField("$alias.$key.$i._joinData.openemis_no");
                        $Form->unlockField("$alias.$key.$i._joinData.name");
                        $Form->unlockField("$alias.$key.$i._joinData.security_user_id");
                        $Form->unlockField("$alias.$key.$i._joinData.security_role_id_order");
                        $rowData[] = $joinData['openemis_no'];
                        $rowData[] = $name;
                        $rowData[] = $HtmlField->secureSelect("$alias.$key.$i._joinData.security_role_id", ['label' => false, 'options' => $roleOptions]);
                        $Form->unlockField("$alias.$key.$i._joinData.security_role_id");
                        $rowData[] = $this->getDeleteButton();
                        $tableCells[] = $rowData;
                    } else if ($entity->isNew()) {
                        if (!$this->AccessControl->isAdmin()) {
                            // If this is a new user group
                            $rowData = [];
                            $name = $joinData['name'];
                            $name .= $Form->hidden("$alias.$key.$i.id", ['value' => $joinData['security_user_id']]);
                            $name .= $Form->hidden("$alias.$key.$i._joinData.openemis_no", ['value' => $joinData['openemis_no']]);
                            $name .= $Form->hidden("$alias.$key.$i._joinData.name", ['value' => $joinData['name']]);
                            $name .= $Form->hidden("$alias.$key.$i._joinData.security_user_id", ['value' => $joinData['security_user_id']]);
                            $name .= $Form->hidden("$alias.$key.$i._joinData.security_role_id", ['value' => $this->Roles->getGroupAdministratorEntity()->id]); //get the Group Administrator role ID
                            $Form->unlockField("$alias.$key.$i.id");
                            $Form->unlockField("$alias.$key.$i._joinData.openemis_no");
                            $Form->unlockField("$alias.$key.$i._joinData.name");
                            $Form->unlockField("$alias.$key.$i._joinData.security_user_id");
                            $Form->unlockField("$alias.$key.$i._joinData.security_role_id");
                            $rowData[] = $joinData['openemis_no'];
                            $rowData[] = $name;
                            $rowData[] = __('Group Administrator');
                            $rowData[] = ''; //creator could not be removed.
                            $tableCells[] = $rowData;
                        }
                    }
                }
            }
        }
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->subject()->renderElement('Security.Groups/' . $key, ['attr' => $attr]);
    }

    public function addEditOnAddUser(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $alias = $this->alias();

        if ($data->offsetExists('user_id')) {
            $id = $data['user_id'];
            try {
                $obj = $this->Users->get($id);

                if (!array_key_exists('users', $data[$alias])) {
                    $data[$alias]['users'] = [];
                }
                $data[$alias]['users'][] = [
                    'id' => $obj->id,
                    '_joinData' => ['openemis_no' => $obj->openemis_no, 'security_user_id' => $obj->id, 'name' => $obj->name, 'security_role_id_order' => '']
                ];
            } catch (RecordNotFoundException $ex) {
                $this->log(__METHOD__ . ': Record not found for id: ' . $id, 'debug');
            }
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('no_of_users', ['visible' => ['index' => true]]);
        $this->setFieldOrder(['name', 'no_of_users']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $queryParams = $this->request->query;

        $query->find('notInInstitutions');

        // filter groups by users permission
        if ($this->Auth->user('super_admin') != 1) {
            $userId = $this->Auth->user('id');
            $query->where([
                'OR' => [
                    'EXISTS (SELECT `id` FROM `security_group_users` WHERE `security_group_users`.`security_group_id` = `UserGroups`.`id` AND `security_group_users`.`security_user_id` = ' . $userId . ')',
                    'UserGroups.created_user_id' => $userId
                ]
            ]);
        }
        $extra['order'] = [$this->aliasField('name') => 'asc'];

        $search = $this->getSearchKey();

        // CUSTOM SEACH - Institution Code, Institution Name, Area Code and Area Name
        $extra['auto_search'] = false; // it will append an AND
        if (!empty($search)) {
            $query->find('byInstitutionAreaNameCode', ['search' => $search]);
        }
    }

    public function findByInstitutionAreaNameCode(Query $query, array $options)
    {
        if (array_key_exists('search', $options)) {
            $search = $options['search'];
            $query
            ->join([
                [
                    'table' => 'security_group_institutions', 'alias' => 'SecurityGroupInstitutions', 'type' => 'LEFT',
                    'conditions' => ['SecurityGroupInstitutions.security_group_id = ' . $this->aliasField('id')]
                ],
                [
                    'table' => 'institutions', 'alias' => 'Institutions', 'type' => 'LEFT',
                    'conditions' => [
                        'Institutions.id = ' . 'SecurityGroupInstitutions.institution_id',
                    ]
                ],
                [
                    'table' => 'security_group_areas', 'alias' => 'SecurityGroupAreas', 'type' => 'LEFT',
                    'conditions' => ['SecurityGroupAreas.security_group_id = ' . $this->aliasField('id')]
                ],
                [
                    'table' => 'areas', 'alias' => 'Areas', 'type' => 'LEFT',
                    'conditions' => [
                        'Areas.id = ' . 'SecurityGroupAreas.area_id',
                    ]
                ],
            ])
            ->where([
                    'OR' => [
                        ['Institutions.code LIKE' => '%' . $search . '%'],
                        ['Institutions.name LIKE' => '%' . $search . '%'],
                        ['Areas.code LIKE' => '%' . $search . '%'],
                        ['Areas.name LIKE' => '%' . $search . '%'],
                        [$this->aliasField('name').' LIKE' => '%'.$search.'%']
                    ]
                ]
            )
            ->group($this->aliasField('id'))
            ;
        }

        return $query;
    }

    public function findNotInInstitutions(Query $query, array $options)
    {
        $query->where([
            'NOT EXISTS (SELECT `id` FROM `institutions` WHERE `security_group_id` = `UserGroups`.`id`)'
        ]);
        return $query;
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        // delete all areas if no areas remains in the table
        if (!array_key_exists('areas', $data[$this->alias()])) {
            $data[$this->alias()]['areas'] = [];
        }

        if (!array_key_exists('institutions', $data[$this->alias()])) {
            $data[$this->alias()]['institutions'] = [];
        }

        if (!array_key_exists('users', $data[$this->alias()])) {
            $data[$this->alias()]['users'] = [];
        }

        // in case user has been added with the same role twice, we need to filter it
        $this->filterDuplicateUserRoles($data);

        // Required by patchEntity for associated data
        $newOptions = [];
        $newOptions['associated'] = [
            'Areas' => [
                'validate' => false
            ],
            'Institutions' => [
                'validate' => false
            ],
            'Users'
        ];

        $arrayOptions = $options->getArrayCopy();
        $arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
        $options->exchangeArray($arrayOptions);
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [ //this will exclude checking during remove restrict
            $this->Areas->alias(),
            $this->Institutions->alias(),
            $this->Roles->alias(),
            'SecurityGroupUsers'
        ];
    }

    public function getAssociatedRecordConditions(Event $event, Query $query, $assocTable, ArrayObject $extra)
    {
        //additional condition to exclude current user to the user inside the group counter.
        if ($assocTable->alias() == 'SecurityGroupUsers') {
            $query->where([$assocTable->aliasField('security_user_id != ') => $this->Auth->user('id')]);
        }
    }

    // also exists in SystemGroups
    private function filterDuplicateUserRoles(ArrayObject $data)
    {
        if (array_key_exists('users', $data[$this->alias()])) {
            $roles = [];

            $users = $data[$this->alias()]['users'];
            foreach ($users as $i => $user) {
                $joinData = $user['_joinData'];
                $userRole = $joinData['security_user_id'] . ' - ' . $joinData['security_role_id'];
                if (in_array($userRole, $roles)) {
                    unset($data[$this->alias()]['users'][$i]);
                } else {
                    $roles[] = $userRole;
                }
            }
        } else {
            $data[$this->alias()]['users'] = [];
        }
    }

    public function findByUser(Query $query, array $options)
    {
        $userId = $options['userId'];
        $alias = $this->alias();

        $query
        ->join([
            [
                'table' => 'security_group_users',
                'alias' => 'SecurityGroupUsers',
                'type' => 'LEFT',
                'conditions' => ["SecurityGroupUsers.security_group_id = $alias.id"]
            ]
        ])
        ->where([
            'OR' => [
                "$alias.created_user_id" => $userId,
                'SecurityGroupUsers.security_user_id' => $userId
            ]
        ])
        ->group([$this->aliasField('id')]);
        return $query;
    }

    public function onGetNoOfUsers(Event $event, Entity $entity)
    {
        $id = $entity->id;

        $GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $count = $GroupUsers->findAllBySecurityGroupId($id)->count();

        return $count;
    }

    public function ajaxAreaAutocomplete()
    {
        $this->controller->autoRender = false;
        $this->ControllerAction->autoRender = false;

        if ($this->request->is(['ajax'])) {
            $term = $this->request->query['term'];
            $data = $this->Areas->autocomplete($term);
            echo json_encode($data);
            die;
        }
    }

    public function ajaxInstitutionAutocomplete()
    {
        $this->controller->autoRender = false;
        $this->ControllerAction->autoRender = false;

        if ($this->request->is(['ajax'])) {
            $term = $this->request->query['term'];
            $data = $this->Institutions->autocomplete($term);
            echo json_encode($data);
            die;
        }
    }

    public function ajaxUserAutocomplete()
    {
        $this->controller->autoRender = false;
        $this->ControllerAction->autoRender = false;

        if ($this->request->is(['ajax'])) {
            $term = $this->request->query['term'];
            $data = $this->Users->autocomplete($term);
            echo json_encode($data);
            die;
        }
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->data['area_search'] = '';
        $this->request->data['institution_search'] = '';
        $this->request->data['user_search'] = '';
    }
}
