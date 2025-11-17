<?php

namespace Security\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;
use Cake\ORM\ResultSet;
use Cake\Datasource\Exception\RecordNotFoundException;

class PermissionsTable extends ControllerActionTable
{
    private $operations = ['_view', '_edit', '_add', '_delete', '_execute'];

    public function initialize(array $config): void
    {
        $this->setTable('security_role_functions');
        parent::initialize($config);

        $this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);
        $this->belongsTo('SecurityFunctions', ['className' => 'Security.SecurityFunctions']);
        $this->toggle('add', false);
        $this->toggle('view', false);
        $this->toggle('search', false);
    }

    private function check($entity, $operation)
    {
        $flag = 0;
        if (empty($entity->{$operation})) {
            $flag = -1;
        } else if (!empty($entity->Permissions)) {
            if (!is_null($entity->Permissions[$operation])) {
                $flag = $entity->Permissions[$operation];
            }
        }
      //  echo "<pre>"; print_r($entity); die;
        return $flag;
    }

    public function afterAction(EventInterface $event, ArrayObject $options)
    {
        $plugin = __($this->controller->getPlugin());
        $id = $this->request->getAttribute('params')['pass'][1];
        $DecodedQueryString = $this->paramsDecode($id);
        try {
            $name = $this->SecurityRoles->get($DecodedQueryString['id']);
            $getRoleName = $name['name'];
            $this->controller->set('contentHeader', $plugin . ' - ' . $getRoleName);
        } catch (RecordNotFoundException $e) {
            Log::write('error', $e->getMessage());
        }
    }


    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $controller = $this->controller;

        $this->field('function');
        $this->field('security_role_id', ['visible' => false]);
        $this->field('security_function_id', ['visible' => false]);

        $checkboxOptions = ['tableColumnClass' => 'permission-column'];
        $this->field('_view', $checkboxOptions);
        $this->field('_edit', $checkboxOptions);
        $this->field('_add', $checkboxOptions);
        $this->field('_delete', $checkboxOptions);
        $this->field('_execute', $checkboxOptions);

        $modules = ['Institutions',
            'Directory',
            'Reports',
            'Administration',
            'Personal',
            'Guardian',
            'API']; // POCOR-8966 start
        $this->setupTabElements($modules);

        $module = $this->request->getQuery('module');
        if (empty($module)) {
            $module = current($modules);
            $this->request = $this->request->withQueryParams(['module' => $module]);
        }
        $controller->set('selectedAction', $module);
        $controller->set('operations', $this->operations);
    }

    // Event: ControllerAction.Model.index.beforeAction
    public function indexBeforeActionbak(EventInterface $event, ArrayObject $extra)
    {
        $query = $extra['query'];
        $controller = $this->controller;
        if (count($this->request->getParam('pass')) != 2) { // POCOR-8074
            $event->stopPropagation();
            return $this->controller->redirect(['action' => 'Roles']);
        }

        $roleId = $this->paramsDecode($this->request->getAttribute('params')['pass'][1]);
        if (!$this->checkRolesHierarchy($roleId)) {
            $action = [
                'plugin' => 'Security',
                'controller' => 'Securities',
                'action' => $this->getAlias(), // POCOR-8074
                '0' => 'index'
            ];
            $event->stopPropagation();
            return $this->controller->redirect($action);
        }

        $module = $this->request->getQuery('module');
        $extra['pagination'] = false;
        $extra['auto_contain'] = false;
        $id = $this->request->getParam('pass')[1];
        $attr = [
            'escape' => false,
            'data-placement' => 'bottom',
            'data-toggle' => 'tooltip',
            'class' => 'btn btn-xs btn-default'
        ];
        $toolbarButtons = $extra['toolbarButtons'];
        $toolbarButtons['back']['type'] = 'button';
        $toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
        $toolbarButtons['back']['attr'] = $attr;
        $toolbarButtons['back']['attr']['title'] = __('Back');
        $toolbarButtons['back']['url']['action'] = 'Roles';

        $toolbarButtons['edit']['url'] = $this->url('edit');
        $toolbarButtons['edit']['type'] = 'button';
        $toolbarButtons['edit']['label'] = '<i class="fa kd-edit"></i>';
        $toolbarButtons['edit']['attr'] = $attr;
        $toolbarButtons['edit']['attr']['title'] = __('Edit');

        // Log roleId and module
        Log::write('debug', 'Role ID: ' . print_r($roleId, true));
        Log::write('debug', 'Module: ' . print_r($module, true));

        // Ensure roleId and module are strings
        //POCOR-8345 start
        if (is_array($roleId)) {
            Log::write('error', 'Role ID is an array: ' . print_r($roleId, true));
            $roleId = json_encode($roleId); // Convert array to JSON string
        } else {
            $roleId = (string)$roleId;
        }

        if (is_array($module)) {
            Log::write('error', 'Module is an array: ' . print_r($module, true));
            $module = json_encode($module); // Convert array to JSON string
        } else {
            $module = (string)$module;
        }
        //POCOR-8345 end
        // Correct query construction
        $query = $this->SecurityFunctions->find('permissions', ['roleId' => $roleId, 'module' => $module]);
        $extra['query'] = $query;

    }

    public function indexAfterAction(EventInterface $event,
                                     Query $query,
                                     Array $data, //POCOR-8074
                                     ArrayObject $extra)
    {
        $list = [];
        $icons = [
            -1 => '<i class="fa fa-minus grey"></i>',
            0 => '<i class="fa kd-cross red"></i>',
            1 => '<i class="fa kd-check green"></i>'
        ];

        foreach ($data as $obj) {
            if ($obj->name == 'Securities' && $obj->controller == 'ApiSecurities') {
                continue;
            } //POCOR-7520 remove Securities option from Adminsitration tab roles permission list in API section.
            if (!array_key_exists($obj->category, $list)) {
                $list[$obj->category] = [];
            }
            foreach ($this->operations as $op) {
                $flag = $this->check($obj, $op);
               // echo "<pre>"; print_r($obj); die;
                $obj->Permissions[$op] = $icons[$flag];
            }

            $obj->name = __( (string)$obj->name);

            // if the permission have description, it will display the description tooltip next to the permission name.
            if (!empty($obj['description'])) {
                $message = $obj['description'];
                $obj->name = $obj->name . $this->tooltipMessage($message);
            }

            $list[$obj->category][] = $obj;
        }

        return $list;
    }

    public function checkRolesHierarchy($roleId)
    {
        $user = $this->Auth->user();
        $userId = $user['id'];
        if ($user['super_admin'] == 1) { // super admin will show all roles
            $userId = null;
        }
        $GroupRoles = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
        //POCOR-8074 start
        if ($userId) {
            $where = [
                $GroupRoles->aliasField('security_user_id') => $userId
            ];
        } else {
            $where = [
                $GroupRoles->aliasField('security_user_id IS NULL')
            ];
        }
        //POCOR-8074 end
        $userRole = $GroupRoles
            ->find()
            ->contain('SecurityRoles')
            ->order(['SecurityRoles.order'])
            ->where($where)
            ->first();

        $SecurityRolesTable = $this->SecurityRoles;

        $roleEntity = $SecurityRolesTable->get($roleId);

        $roleOrder = $roleEntity->order;

        //this is to check if user have role higher that the one user try to edit.  e.g. teacher(4) and principal(2)
        //also for super admin where redirect not necessary
        //OR user is creator of the user role.
        return (($roleOrder > $userRole['security_role']['order']) || ($roleEntity->created_user_id == $userId));
    }

    private function setupTabElements($modules)
    {
        $controller = $this->controller;
        $tabElements = [];
        $url = ['plugin' => $controller->getPlugin(), 'controller' => $controller->getName(), 'action' => $this->getAlias()];
        if (!empty($this->request->getParam('pass'))) { //POCOR-8074
            $url = array_merge($url, $this->request->getParam('pass'));
        }

        if (!empty($this->request->getQuery())) {
            $url['?'] = $this->request->getQuery(); //POCOR-8074
        }

        foreach ($modules as $module) {
            $moduleUrl = $url; //POCOR-8074
            $moduleUrl['?']['module'] = $module; //POCOR-8074
            $tabElements[$module] = [
                'url' => $moduleUrl, //POCOR-8074
                'text' => __($module)
            ];
        }
        $tabElements = $controller->TabPermission->checkTabPermission($tabElements);
        $controller->set('tabElements', $tabElements);
    }

    private function tooltipMessage($message)
    {
        $tooltipMessage = '&nbsp&nbsp;<i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $message . '"></i>';

        return $tooltipMessage;
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $query = $extra['query'];
        $controller = $this->controller;
        if (count($this->request->getParam('pass')) != 2) { // POCOR-8074
            $event->stopPropagation();
            return $this->controller->redirect(['action' => 'Roles']);
        }

        $roleId = $this->paramsDecode($this->request->getAttribute('params')['pass'][1]);
        if (!$this->checkRolesHierarchy($roleId)) {
            $action = [
                'plugin' => 'Security',
                'controller' => 'Securities',
                'action' => $this->getAlias(), // POCOR-8074
                '0' => 'index'
            ];
            $event->stopPropagation();
            return $this->controller->redirect($action);
        }

        $module = $this->request->getQuery('module');
        $extra['pagination'] = false;
        $extra['auto_contain'] = false;
        $id = $this->request->getParam('pass')[1];
        $attr = [
            'escape' => false,
            'data-placement' => 'bottom',
            'data-toggle' => 'tooltip',
            'class' => 'btn btn-xs btn-default'
        ];
        $toolbarButtons = $extra['toolbarButtons'];
        $toolbarButtons['back']['type'] = 'button';
        $toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
        $toolbarButtons['back']['attr'] = $attr;
        $toolbarButtons['back']['attr']['title'] = __('Back');
        $toolbarButtons['back']['url']['action'] = 'Roles';

        $toolbarButtons['edit']['url'] = $this->url('edit');
        $toolbarButtons['edit']['type'] = 'button';
        $toolbarButtons['edit']['label'] = '<i class="fa kd-edit"></i>';
        $toolbarButtons['edit']['attr'] = $attr;
        $toolbarButtons['edit']['attr']['title'] = __('Edit');

        // Log roleId and module
        //Log::write('debug', 'Role ID: ' . print_r($roleId, true));
        //Log::write('debug', 'Module: ' . print_r($module, true));

        // Ensure roleId and module are strings
        if (is_array($roleId)) {
            //Log::write('error', 'Role ID is an array: ' . print_r($roleId, true));
            $roleId = json_encode($roleId); // Convert array to JSON string
        } else {
            $roleId = (string)$roleId;
        }

        if (is_array($module)) {
            //Log::write('error', 'Module is an array: ' . print_r($module, true));
            $module = json_encode($module); // Convert array to JSON string
        } else {
            $module = (string)$module;
        }

        $roleIdDecoded = json_decode($roleId, true); // Decode the JSON string to an array
        $roleId = $roleIdDecoded['id'] ?? null;

        // Correct query construction
        $query = $this->SecurityFunctions->find('permissions', ['roleId' => $roleId, 'module' => $module]);
        $extra['query'] = $query;

        return $query;
    }

}
