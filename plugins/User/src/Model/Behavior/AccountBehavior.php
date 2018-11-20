<?php
namespace User\Model\Behavior;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

class AccountBehavior extends Behavior
{
    private $isInstitution = false;
    private $userRole = null;
    private $targetField = 'password';
    private $passwordAllowEmpty = false;

    public function initialize(array $config)
    {
        $this->_table->table('security_users');
        $this->_table->entityClass('User.User');
        parent::initialize($config);

        $this->userRole = (array_key_exists('userRole', $config))? $config['userRole']: null;
        $this->targetField = (array_key_exists('targetField', $config))? $config['targetField']: $this->targetField;
        $this->passwordAllowEmpty = (array_key_exists('passwordAllowEmpty', $config))? $config['passwordAllowEmpty']: $this->passwordAllowEmpty;
        $this->isInstitution = (array_key_exists('isInstitution', $config))? $config['isInstitution']: $this->isInstitution;

        $this->_table->belongsToMany('Roles', [
            'className' => 'Security.SecurityRoles',
            'joinTable' => 'security_group_users',
            'foreignKey' => 'security_user_id',
            'targetForeignKey' => 'security_role_id',
            'through' => 'Security.SecurityGroupUsers',
            'dependent' => true
        ]);

        $checkOwnPassword = ($this->userRole == 'Preferences');
        $this->_table->addBehavior('Security.Password', [
            'field' => $this->targetField,
            'checkOwnPassword' => $checkOwnPassword,
            'passwordAllowEmpty' => $this->passwordAllowEmpty,
            'createRetype' => true,
        ]);
    }

    private function setupTabElements($entity)
    {
        if ($this->userRole == 'Preferences') {
            return; // has its own setupTabElements
        }
        $id = !is_null($this->_table->request->query('id')) ? $this->_table->request->query('id') : 0;

        $options = [
            'userRole' => Inflector::singularize($this->userRole),
            'action' => $this->_table->action,
            'id' => $id,
            'userId' => $entity->id
        ];

        if ($this->_table->action != 'add') {
            if ($this->isInstitution) {
                // url of tabElements is build in Institution->getUserTabElements()
            } else {
                $options['id'] = $entity->id;
            }
        }

        if ($this->_table->controller->name == 'Guardians') {
            $tabElements = $this->_table->controller->getGuardianTabElements($options);
        } else {
            $tabElements = $this->_table->controller->getUserTabElements($options);
        }

        $this->_table->controller->set('tabElements', $tabElements);
        $this->_table->controller->set('selectedAction', $this->_table->alias());
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $this->_table->ControllerAction->field('roles', [
            'type' => 'role_table',
            'valueClass' => 'table-full-width',
            'visible' => ['index' => false, 'view' => true, 'edit' => false]
        ]);
        $this->_table->ControllerAction->setFieldOrder(['username', 'last_login', 'roles']);

        $this->afterActionCode($event, $entity);
    }

    public function editAfterAction(Event $event, Entity $entity)
    {
        $this->_table->ControllerAction->field('username');
        $this->_table->ControllerAction->setFieldOrder(['username', 'password', 'retype_password']);

        $this->afterActionCode($event, $entity);
    }

    // called manually cos need to use $entity
    private function afterActionCode(Event $event, Entity $entity)
    {
        $fieldsNeeded = ['username','password', 'roles', 'new_password', 'retype_password'];
        foreach ($this->_table->fields as $key => $value) {
            if (!in_array($key, $fieldsNeeded)) {
                $this->_table->fields[$key]['visible'] = false;
            } else {
                $this->_table->fields[$key]['visible'] = true;
            }
        }

        $this->_table->ControllerAction->field('last_login', ['visible' => ['view' => true, 'edit' => false]]);
        $this->_table->ControllerAction->field('password', ['type' => 'password', 'visible' => ['view' => false, 'edit' => true], 'attr' => ['value' => '', 'autocomplete' => 'off']]);

        $orderFields = [];
        foreach ($fieldsNeeded as $key => $value) {
            if (array_key_exists($value, $this->_table->fields)) {
                $orderFields[] = $value;
            }
        }

        $this->_table->ControllerAction->setFieldOrder($orderFields);

        if (strtolower($this->_table->action) != 'index') {
            if (!$this->isInstitution) {
                $this->_table->Navigation->addCrumb($this->_table->getHeader($this->_table->action));
            }
        }

        $this->setupTabElements($entity);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
        $events['ControllerAction.Model.view.beforeQuery'] = 'viewBeforeQuery';
        $events['ControllerAction.Model.edit.afterAction'] = 'editAfterAction';
        $events['ControllerAction.Model.edit.beforePatch'] = 'editBeforePatch';
        $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
        $events['ControllerAction.Model.onUpdateFieldUsername'] = 'onUpdateFieldUsername';
        return $events;
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        // trimming passwords
        $dataArray = $data->getArrayCopy();
        if (array_key_exists($this->_table->alias(), $dataArray)) {
            if (array_key_exists('username', $dataArray[$this->_table->alias()])) {
                $data[$this->_table->alias()]['username'] = trim($dataArray[$this->_table->alias()]['username']);
            }
        }
    }



    public function viewBeforeQuery(Event $event, Query $query)
    {
        $options['auto_contain'] = false;
        $query->contain(['Roles']);
    }


    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        if ($action == 'view') {
            if ($toolbarButtons->offsetExists('back')) {
                unset($toolbarButtons['back']);
            }
        }
    }

    public function onUpdateFieldUsername(Event $event, array $attr, $action, Request $request)
    {
        $isAdmin = $this->_table->AccessControl->isAdmin();
        $permission = is_array($this->config('permission')) ? $this->config('permission') : [];
        $isAuthorised = $this->_table->AccessControl->check($permission);
        $controller = $this->_table->controller->name;
        $loginUserId = $this->_table->Auth->user('id');
        $id = $request->params['pass'][1];
        if ($action == 'edit' && (($isAdmin && $loginUserId == $id) || !$isAuthorised || (!$isAdmin && $this->config('userRole') == 'Securities')) || $controller == 'Preferences') {
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    public function onGetRoleTableElement(Event $event, $action, $entity, $attr, $options = [])
    {
        $tableHeaders = [__('Groups'), __('Roles')];
        $tableCells = [];
        $key = 'roles';
        if ($action == 'view') {
            $GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
            $groupUserRecords = $GroupUsers->find()
                ->matching('SecurityGroups')
                ->matching('SecurityRoles')
                ->where([$GroupUsers->aliasField('security_user_id') => $entity->id])
                ->group([
                    $GroupUsers->aliasField('security_group_id'),
                    $GroupUsers->aliasField('security_role_id')
                ])
                ->select(['group_name' => 'SecurityGroups.name', 'role_name' => 'SecurityRoles.name'])
                ->all();
            foreach ($groupUserRecords as $obj) {
                $rowData = [];
                $rowData[] = $obj->group_name;
                $rowData[] = $obj->role_name;
                $tableCells[] = $rowData;
            }
        }
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->subject()->renderElement('User.Accounts/' . $key, ['attr' => $attr]);
    }
}
