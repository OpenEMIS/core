<?php
namespace App\Controller\Component;

use Cake\I18n\Time;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

class AccessControlComponent extends Component
{
    private $controller;
    private $action;
    private $Session;
    private $accessMap;

    protected $_defaultConfig = [
        'operations' => ['_view', '_add', '_edit', '_delete', '_execute'],
        'ignoreList' => [],
        'separator' => '|'
    ];

    public $components = ['Auth', 'Page.Page'];

    public function initialize(array $config)
    {
        $this->controller = $this->_registry->getController();
        $this->action = $this->request->params['action'];
        $this->Session = $this->request->session();
        $this->accessMap = [];

        if (!is_null($this->Auth->user()) && $this->Auth->user('super_admin') == 0) {
            if (!$this->Session->check('Permissions')) {
                $this->buildPermissions();
            } else {
                // check if permission is updated and rebuild
                $userId = $this->Auth->user('id');
                if ($this->isChanged($userId)) {
                    $this->buildPermissions();
                }
            }
        } elseif ($this->Auth->user('super_admin') == 1) {
            $this->Session->write('System.User.roles', __('System Administrator'));
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        // need to execute before PageComponent::beforeRender
        $events['Controller.beforeRender'] = ['callable' => 'beforeRender', 'priority' => 4];
        return $events;
    }

    // Is called after the controller executes the requested action’s logic, but before the controller renders views and layout.
    public function beforeRender(Event $event)
    {
        if ($this->controller instanceof \Page\Controller\PageController) {
            $page = $this->Page;
            $allowedActions = ['index', 'view', 'add', 'edit', 'delete', 'download'];
            $actions = $page->getActions();
            $disabledActions = [];

            foreach ($actions as $action => $value) {
                if ($value == true && in_array($action, $allowedActions)) {
                    $check = $this->check(['controller' => $this->controller->name, 'action' => $action]);
                    if ($check == false) {
                        $disabledActions[] = $action;
                    }
                }
            }

            $page->disable($disabledActions);
        }
    }

    private function getUserGroupRole()
    {
        $rolesList = $this->getRolesByUser();
        $roles = [];
        foreach ($rolesList as $obj) {
            if (!empty($obj->security_group) && !empty($obj->security_role)) {
                $roles[] = sprintf("%s (%s)", $obj->security_group->name, $obj->security_role->name);
            }
        }
        return implode('<br/>', $roles);
    }

    public function isChanged($userId)
    {
        $isChanged = false;
        $userRole = $this->getUserGroupRole();
        if ($this->Session->check('System.User.roles')) {
            $sessionUserRole = $this->Session->read('System.User.roles');
            if ($userRole !== $sessionUserRole) {
                $isChanged = true;
            }
        }

        $SecurityRoleFunctions = TableRegistry::get('Security.SecurityRoleFunctions');
        $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');

        $roles = $SecurityGroupUsers
            ->find()
            ->select([$SecurityGroupUsers->aliasField('security_role_id')])
            ->where([$SecurityGroupUsers->aliasField('security_user_id') => $userId]);

        $selectedColumns = [
            'modified' => '(
				CASE
					WHEN '.$SecurityRoleFunctions->aliasField('modified').' > '.$SecurityRoleFunctions->aliasField('created').'
					THEN '.$SecurityRoleFunctions->aliasField('modified').'
					ELSE '.$SecurityRoleFunctions->aliasField('created').'
					END
				)'
        ];

        if ($roles->all()->count() > 0) {
            $entity = $SecurityRoleFunctions
                ->find()
                ->select($selectedColumns)
                ->where([$SecurityRoleFunctions->aliasField('security_role_id') . ' IN' => $roles])
                ->order(['modified' => 'DESC'])
                ->first();

            if (!is_null($entity)) {
                $lastModified = $this->Session->read('Permissions.lastModified');
                if (empty($lastModified)) {
                    $isChanged = true;
                } else {
                    if (!is_null($entity->modified) && $entity->modified->gt($lastModified)) {
                        $isChanged = true;
                    }
                }
            }
        }
        return $isChanged;
    }

    public function buildPermissions()
    {
        $this->Session->delete('Permissions'); // remove all permission first
        $operations = $this->config('operations');
        $separator = $this->config('separator');
        $userId = $this->Auth->user('id');
        $GroupRoles = TableRegistry::get('Security.SecurityGroupUsers');

        $SecurityRoleFunctions = TableRegistry::get('Security.SecurityRoleFunctions');
        $roles = $GroupRoles->find()
            ->where([
                $GroupRoles->aliasField('security_user_id').'='.$userId
            ])
            ->group([$GroupRoles->aliasField('security_role_id')])
            ->select(['security_role_id' => $GroupRoles->aliasField('security_role_id')]);

        $selectedColumns = [
            'modified' => '(
				CASE
					WHEN '.$SecurityRoleFunctions->aliasField('modified').' > '.$SecurityRoleFunctions->aliasField('created').'
					THEN '.$SecurityRoleFunctions->aliasField('modified').'
					ELSE '.$SecurityRoleFunctions->aliasField('created').'
					END
				)'
        ];

        if ($roles->all()->count() > 0) {
            // Newly created system role or user role will have no security functions prepopulated in the table,
            // thus the following may return empty
            $lastModified = $SecurityRoleFunctions->find()
                ->select($selectedColumns)
                ->where([$SecurityRoleFunctions->aliasField('security_role_id') . ' IN' => $roles])
                ->order(['modified' => 'DESC'])
                ->first();

            if (!empty($lastModified)) {
                $lastModified = $lastModified->modified;
            } else {
                $lastModified = '';
            }

            foreach ($roles->all() as $role) { // for each role in user
                $roleId = $role->security_role_id;

                $functions = $SecurityRoleFunctions->find()
                    ->contain(['SecurityFunctions'])
                    ->where([$SecurityRoleFunctions->aliasField('security_role_id') => $roleId])
                    ->all();

                foreach ($functions as $entity) { // for each function in roles
                    if (!empty($entity->security_function)) {
                        $function = $entity->security_function;

                        foreach ($operations as $op) { // for each operation in function
                            if (!empty($function->{$op}) && $entity->{$op} == 1) {
                                $actions = explode($separator, $function->{$op});

                                if (is_array($actions)) {
                                    foreach ($actions as $action) { // for each action in operation
                                        if (!empty($action)) {
                                            $permission = implode('.', [$function->controller, $action]);
                                            $this->addPermission($permission, $roleId);
                                        }
                                    }
                                } else {
                                    $permission = implode('.', [$function->controller, $action]);
                                    $this->addPermission($permission, $roleId);
                                }
                            }
                        }
                    }
                }
            }

            $userRole = $this->getUserGroupRole();
            $this->Session->write('System.User.roles', $userRole);
            $this->Session->write('Permissions.lastModified', $lastModified);
        } else {
            $this->Session->write('System.User.roles', '');
            $this->Session->write('Permissions.lastModified', '');
        }
    }

    public function addPermission($permission, $roleId)
    {
        $permissionKey = 'Permissions.' . $permission;
        if (!$this->Session->check($permissionKey)) {
            $this->Session->write($permissionKey, [$roleId]);
        } else {
            $roles = $this->Session->read($permissionKey);
            if (!in_array($roleId, $roles)) {
                $roles[] = $roleId;
            }
            $this->Session->write($permissionKey, $roles);
        }
    }

    public function check($url = [], $roleIds = [])
    {
        $superAdmin = $this->Auth->user('super_admin');

        if ($superAdmin || !is_array($url)) { // if $url is a string, then skip checking of permission
            return true;
        }

        // we only need controller and action
        foreach ($url as $i => $val) {
            if (($i != 'controller' && $i != 'action' && !is_numeric($i)) || is_numeric($val) || empty($val) || $this->isUuid($val) || $this->isSHA256($val) || $this->isEncodedParam($val) || $this->isHex($val)) {
                unset($url[$i]);
            }
        }
        // Log::write('debug', $url);

        if (empty($url)) {
            $url = ['controller' => $this->controller->name, 'action' => $this->action];
        } else {
            if (!array_key_exists('controller', $url)) {
                $url['controller'] = $this->controller->name;
            }
        }
        $url = $this->checkAccessMap($url);
        $checkUrl = [];

        if (!(array_key_exists('controller', $url) && array_key_exists('action', $url) && array_key_exists('0', $url))) {
            if (array_key_exists('0', $url) && array_key_exists('1', $url) && array_key_exists('2', $url)) {
                $url['controller'] = $url[0];
                $url['action'] = $url[1];
                $url[0] = $url[2];
                unset($url[1]);
                unset($url[2]);
            } elseif (array_key_exists('0', $url) && array_key_exists('1', $url)) {
                $url['controller'] = $url[0];
                $url['action'] = $url[1];
                unset($url[0]);
                unset($url[1]);
            }
        }

        // exclude profile controllers
        $excludedController = ['ProfileApplicationAttachments', 'ProfileApplicationInstitutionChoices', 'ProfileBodyMasses', 'ProfileComments', 'ProfileInsurances', 'Profiles', 'ScholarshipsDirectory'];
        if (isset($url['controller']) && in_array($url['controller'], $excludedController)) {
            return true;
        }

        if (array_key_exists('controller', $url)) {
            $checkUrl[] = $url['controller'];
            unset($url['controller']);
        }
        if (array_key_exists('action', $url)) {
            $checkUrl[] = $url['action'];
            unset($url['action']);
        }
        $controller = $checkUrl[0];
        $action = $checkUrl[1];
        $url = array_merge($checkUrl, $url);
        $url = array_merge(['Permissions'], $url);
        $permissionKey = implode('.', $url);
        // Log::write('debug', $permissionKey);

        if ($controller == $this->controller->name) {
            $event = $this->controller->dispatchEvent('Controller.SecurityAuthorize.isActionIgnored', [$action], $this);
            if ($event->result == true) {
                return true;
            }
        }

        if ($this->Session->check($permissionKey)) {
            if (!empty($roleIds)) {
                $roles = $this->Session->read($permissionKey);
                if (!isset($roles[0])) {
                    if (isset($roles['index'])) {
                        $roles = $roles['index'];
                    } else {
                        $roles = [];
                    }
                }
                return count(array_intersect($roleIds, $roles)) > 0;
            } else {
                // Log::write('debug', $permissionKey);
                return true;
            }
        }
        return false;
    }

    // the purpose of this function is to allow multiple actions linked to the same permission
    public function addAccessMap($key)
    {
        $controller = $this->controller->name;
        $this->accessMap["$controller.$key"] = "$controller.%s";
    }

    public function checkAccessMap($url)
    {
        $urlValues = array_values($url);
        $key = implode('.', [$urlValues[0], $urlValues[1]]);

        $request = $this->request;
        $accessMap = $this->accessMap;

        if (array_key_exists($key, $accessMap)) {
            $action = 'index';
            if (isset($urlValues[2])) {
                if (!is_numeric($urlValues[2]) && !$this->isUuid($urlValues[2])) { // this is an action
                    $action = $urlValues[2];
                }
            } else {
                $paramsPass = $request->params['pass'];
                if (count($paramsPass) > 0) {
                    if (!is_numeric($paramsPass[0]) && !$this->isUuid($paramsPass[0])) { // this is an action
                        $action = array_shift($paramsPass);
                    }
                }
            }
            $url = explode('.', sprintf($accessMap[$key], $action));
        }
        return $url;
    }

    private function isUuid($input)
    {
        if (preg_match('/^\{?[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\}?$/', strtolower($input))) {
            return true;
        } else {
            return false;
        }
    }

    private function isSHA256($input)
    {
        if (preg_match('/^\{?[a-f0-9]{64}\}?$/', strtolower($input))) {
            return true;
        } else {
            return false;
        }
    }

    private function isEncodedParam($input)
    {
        if (strpos($input, '.') !== false) {
            if (count(explode('.', $input)) == 2) {
                return true;
            }
        }
        return false;
    }

    private function isHex($input)
    {
        return ctype_xdigit($input) && strpos($input, '0') !== false;
    }

    public function isAdmin()
    {
        $superAdmin = $this->Auth->user('super_admin');
        return $superAdmin == 1;
    }

    public function getRolesByUser($userId = null)
    {
        if (is_null($userId)) {
            $userId = $this->Auth->user('id');
        }

        $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $data = $SecurityGroupUsers
            ->find()
            ->contain(['SecurityRoles', 'SecurityGroups'])
            ->where([$SecurityGroupUsers->aliasField('security_user_id') => $userId])
            ->group([$SecurityGroupUsers->aliasField('security_group_id'), $SecurityGroupUsers->aliasField('security_role_id')])
            ->all();

        return $data;
    }

    public function getInstitutionsByUser($userId = null)
    {
        $institutionIds = [];

        if (is_null($userId)) {
            $userId = $this->Auth->user('id');
        }

        $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $institutionIds = $SecurityGroupUsers->getInstitutionsByUser($userId);

        return $institutionIds;
        /*
        if (is_null($userId)) {
            $userId = $this->Auth->user('id');
        }

        $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $groupIds = $SecurityGroupUsers
        ->find('list', ['keyField' => 'id', 'valueField' => 'security_group_id'])
        ->where([$SecurityGroupUsers->aliasField('security_user_id') => $userId])
        ->toArray();

        if (!empty($groupIds)) {
            $SecurityGroupInstitutions = TableRegistry::get('Security.SecurityGroupInstitutions');
            $institutionIds = $SecurityGroupInstitutions
            ->find('list', ['keyField' => 'institution_id', 'valueField' => 'institution_id'])
            ->where([$SecurityGroupInstitutions->aliasField('security_group_id') . ' IN ' => $groupIds])
            ->toArray();

            $SecurityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');
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
        */
    }

    public function getAreasByUser($userId = null)
    {
        if (is_null($userId)) {
            $userId = $this->Auth->user('id');
        }

        $SecurityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');
        $areas = $SecurityGroupAreas->getAreasByUser($userId);
        return $areas;
    }
}
