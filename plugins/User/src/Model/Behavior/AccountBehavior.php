<?php
namespace User\Model\Behavior;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

class AccountBehavior extends Behavior
{
    private $isInstitution = false;
    private $userRole = null;
    private $targetField = 'password';
    private $passwordAllowEmpty = false;

    public function initialize(array $config): void
    {
        $this->table()->setTable('security_users');
        $this->table()->setEntityClass('User.User');
        parent::initialize($config);

        $this->userRole = (isset($config['userRole']))? $config['userRole']: null;
        $this->targetField = (isset($config['targetField']))? $config['targetField']: $this->targetField;
        $this->passwordAllowEmpty = (isset($config['passwordAllowEmpty']))? $config['passwordAllowEmpty']: $this->passwordAllowEmpty;
        $this->isInstitution = (isset($config['isInstitution']))? $config['isInstitution']: $this->isInstitution;

        $this->table()->belongsToMany('Roles', [
            'className' => 'Security.SecurityRoles',
            'joinTable' => 'security_group_users',
            'foreignKey' => 'security_user_id',
            'targetForeignKey' => 'security_role_id',
            'through' => 'Security.SecurityGroupUsers',
            'dependent' => true
        ]);

        $checkOwnPassword = ($this->userRole == 'Preferences');
        $this->table()->addBehavior('Security.Password', [
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
        $id = !is_null($this->_table->request->getQuery('id')) ? $this->_table->request->getQuery('id') : 0;
        if(isset($this->userRole)){
            $options = [
                'userRole' => Inflector::singularize($this->userRole),
                'action' => $this->_table->action,
                'id' => $id,
                'userId' => $entity->id
            ];
        }

        if ($this->_table->action != 'add') {
            if ($this->isInstitution) {
                // url of tabElements is build in Institution->getUserTabElements()
            } else {
                $options['id'] = $entity->id;
            }
        }

        $controllerName = $this->_table->controller->getName();

        if ($controllerName == 'Institutions') {
            $tabElementsTab = $this->_table->getBehavior('InstitutionTab');
            $tabElements = $tabElementsTab->setUserTabElements($options);
        }
        if ($controllerName == 'Students') {
            $tabElementsTab = $this->_table->getBehavior('InstitutionTab');
            $tabElements = $tabElementsTab->setUserTabElements($options);
        }
        if ($controllerName == 'Staff') {
            $tabElementsTab = $this->_table->getBehavior('InstitutionTab');
            $tabElements = $tabElementsTab->setUserTabElements($options);
        }
        if ($controllerName == 'Directory' || $controllerName == 'Profiles' || $controllerName == 'Guardians') {
            $tabElements = $this->_table->controller->getUserTabElements($options);
            $this->_table->controller->set('tabElements', $tabElements);
            $this->_table->controller->set('selectedAction', $this->_table->getAlias());
        }
        if ($this->userRole == 'Securities') {
            $tabElements =  $this->_table->controller->getUserTabElements($options);
            $this->_table->controller->set('tabElements', $tabElements);
            $this->_table->controller->set('selectedAction', $this->_table->getAlias());
        }

    }

    public function viewAfterAction(EventInterface $event, Entity $entity)
    {
        $this->_table->ControllerAction->field('roles', [
            'type' => 'role_table',
            'valueClass' => 'table-full-width',
            'visible' => ['index' => false, 'view' => true, 'edit' => false]
        ]);
        $this->_table->ControllerAction->setFieldOrder(['username', 'last_login', 'roles']);

        $this->afterActionCode($event, $entity);
    }

    public function editAfterAction(EventInterface $event, Entity $entity)
    {
        $this->_table->ControllerAction->field('username');
        $this->_table->ControllerAction->setFieldOrder(['username', 'password', 'retype_password']);

        $this->afterActionCode($event, $entity);
    }

    // called manually cos need to use $entity
    private function afterActionCode(EventInterface $event, Entity $entity)
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

    public function implementedEvents(): array
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

    public function editBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        // trimming passwords
        $dataArray = $data->getArrayCopy();
        if (array_key_exists($this->_table->getAlias(), $dataArray)) {
            if (array_key_exists('username', $dataArray[$this->_table->getAlias()])) {
                $data[$this->_table->getAlias()]['username'] = trim($dataArray[$this->_table->getAlias()]['username']);
            }
        }
    }

    public function viewBeforeQuery(EventInterface $event, Query $query)
    {
        $options['auto_contain'] = false;
        $query->contain(['Roles']);
    }


    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        if ($action == 'view') {
            if ($toolbarButtons->offsetExists('back')) {
                unset($toolbarButtons['back']);
            }
        }
    }

    public function onUpdateFieldUsername(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $isAdmin = $this->_table->AccessControl->isAdmin();
        $permission = is_array($this->getConfig('permission')) ? $this->getConfig('permission') : [];
        $isAuthorised = $this->_table->AccessControl->check($permission);
        $controller = $this->_table->controller->getName();
        $loginUserId = $this->_table->Auth->user('id');
        $id = $request->getAttribute('params')['pass'][1];
        if ($action == 'edit' && (($isAdmin && $loginUserId == $id) || !$isAuthorised && $controller != 'Guardians' || (!$isAdmin && $this->getConfig('userRole') == 'Securities')) || $controller == 'Preferences') {
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    public function onGetRoleTableElement(EventInterface $event, $action, $entity, $attr, $options = [])
    {
        $tableHeaders = [__('Groups'), __('Roles')];
        $tableCells = [];
        $key = 'roles';
        if ($action == 'view') {
            $session = $this->_table->request->getSession();
            if($this->_table->request->getParam('controller') == 'Institutions'){
                $institutionId = $this->_table->getBehavior('InstitutionTab')->getInstitutionID();
            }else{
                $institutionId = '';
            }

            $GroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
            $SecurityGroupInstitutions = TableRegistry::getTableLocator()->get('Security.SecurityGroupInstitutions');//POCOR-7309
            //POCOR-7309 starts
            if($this->_table->getAlias() == 'StaffAccount'){
                $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.Staff');
                $SecurityGroups = TableRegistry::getTableLocator()->get('Security.SecurityGroups');
                $SecurityRoles = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
                $groupUserRecords = $InstitutionStaff->find()
    ->select(['group_name' => 'SecurityGroups.name', 'role_name' => 'SecurityRoles.name'])
    ->leftJoin(['SecurityGroupUsers' => $GroupUsers->getTable()], [
        $GroupUsers->aliasField('security_user_id') . ' = ' . $InstitutionStaff->aliasField('staff_id'),
    ])
    ->leftJoin(['SecurityGroups' => $SecurityGroups->getTable()], [
        $SecurityGroups->aliasField('id') . ' = ' . $GroupUsers->aliasField('security_group_id')
    ])
    ->leftJoin(['SecurityGroupInstitutions' => $SecurityGroupInstitutions->getTable()], [
        $SecurityGroupInstitutions->aliasField('security_group_id') . ' = ' . $GroupUsers->aliasField('security_group_id'),
        $SecurityGroupInstitutions->aliasField('institution_id') . ' = ' . $institutionId,
    ])
    ->leftJoin(['SecurityRoles' => $SecurityRoles->getTable()], [
        $SecurityRoles->aliasField('id') . ' = ' . $GroupUsers->aliasField('security_role_id')
    ])
    ->where([
        $InstitutionStaff->aliasField('staff_id') => $entity->id,
        $InstitutionStaff->aliasField('staff_status_id') => 1
    ])
    ->group([
        $GroupUsers->aliasField('security_role_id'),
        $SecurityGroupInstitutions->aliasField('institution_id')
    ])
    ->all();

            }else{//POCOR-7309 ends
                $GroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
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
            }
            foreach ($groupUserRecords as $obj) {
                $rowData = [];
                $rowData[] = $obj->group_name;
                $rowData[] = $obj->role_name;
                $tableCells[] = $rowData;
            }
        }
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->getSubject()->renderElement('User.Accounts/' . $key, ['attr' => $attr]);
    }
}
