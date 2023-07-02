<?php
namespace App\Controller;

use Cake\Event\Event;
use Page\Controller\PageController as BaseController;
use Cake\ORM\Entity;
use Page\Model\Entity\PageElement;
use Cake\Routing\Router;
use Cake\ORM\TableRegistry;//POCOR-7534

class PageController extends BaseController
{
    public $helpers = ['Page.Page'];

    public function initialize()
    {
        parent::initialize();

        $labels = [
            'openemis_no' => 'OpenEMIS ID',
            'modified' => 'Modified On',
            'modified_user_id' => 'Modified By',
            'created' => 'Created On',
            'created_user_id' => 'Created By'
        ];

        $this->Page->config('sequence', 'order');
        $this->Page->config('is_visible', 'visible');
        $this->Page->config('labels', $labels);

        $this->loadComponent('Page.RenderLink');
        $this->loadComponent('RenderDate');
        $this->loadComponent('RenderTime');
        $this->loadComponent('RenderDatetime');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.beforeRender'] = ['callable' => 'beforeRender', 'priority' => 5];
        $events['Controller.Page.onRenderBinary'] = 'onRenderBinary';
        return $events;
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        //POCOR-7534 Starts comment it only for POCOR-7534 ticket's given urls in task
        $session = $this->request->session();
        $superAdmin = $session->read('Auth.User.super_admin');
        if($superAdmin == 0){ 
            $UserData = $session->read('Auth.User')['id'];
            $GroupRoles = TableRegistry::get('Security.SecurityGroupUsers');
            $userRole = $GroupRoles->find()
                        ->contain('SecurityRoles')
                        ->order(['SecurityRoles.order'])
                        ->where([
                            $GroupRoles->aliasField('security_user_id') => $UserData
                        ])
                        ->group([$GroupRoles->aliasField('security_role_id')])
                        ->toArray();
            
            if(!empty($this->request->params['controller']) && !empty($userRole)){
                $RoleIds = [];
                foreach ($userRole as $Role_key => $Role_val) {  $RoleIds[] = $Role_val->security_role_id; }
                $SecurityFunctionIds = $this->getIdBySecurityFunctionName($this->request->params['action'], $this->request->params['controller']);
                if(!empty($SecurityFunctionIds)){
                    $result = $this->checkAuthrizationForRoles($SecurityFunctionIds, $RoleIds);
                    if($result == 0){
                        $event->stopPropagation();
                        $this->Alert->warning('general.notAccess');
                        $this->redirect($this->referer());
                    }
                }
            }
        }//POCOR-7534 Ends

        $page = $this->Page;
        $request = $this->request;
        $action = $request->action;
        $ext = $this->request->params['_ext'];

        if ($ext != 'json') {
            if ($request->is(['put', 'post'])) {
                $page->showElements(true);
            }
            $this->set('menuItemSelected', [$this->name]);

            if ($page->isAutoRender() && in_array($action, ['index', 'view', 'add', 'edit', 'delete'])) {
                $viewFile = 'Page.Page/' . $action;
                $this->viewBuilder()->template($viewFile);
            }
        }
    }
    //POCOR-7534 Starts
    public function getIdBySecurityFunctionName($actionParam, $controllerParam){
        if($actionParam == 'Users' && $controllerParam == 'Securities'){
            $name = 'Users';
        }else if(($actionParam == 'UserGroups' || $actionParam == 'SystemGroups') && $controllerParam == 'Securities'){
            $name = 'Groups';  
        }else if($actionParam == 'Roles' && $controllerParam == 'Securities'){
            $name = ($this->request->query['type'] == 'system') ? 'System Roles' : 'User Roles';
        }else if($actionParam == 'Accounts' && $controllerParam == 'Securities'){
            $name = 'Accounts';  
        }else if($actionParam == 'UserGroupsList' && $controllerParam == 'Securities'){
            $name = 'User Group List';  
        }else if($controllerParam == 'Credentials'){
            $name = 'Credentials';  
        }

        $module = 'Administration';

        $SecurityFunctionsTbl = TableRegistry::get('security_functions');
        $SecurityFunctionsData = $SecurityFunctionsTbl->find()->where([
                                        $SecurityFunctionsTbl->aliasField('name') => $name,
                                        $SecurityFunctionsTbl->aliasField('controller') => $controllerParam,
                                        $SecurityFunctionsTbl->aliasField('module') => $module
                                    ])->toArray();
        $SecurityFunctionIds = [];
        if(!empty($SecurityFunctionsData)){
            foreach ($SecurityFunctionsData as $Function_key => $Function_val) { $SecurityFunctionIds[] = $Function_val->id; }
        }
        return $SecurityFunctionIds;
    }

    public function checkAuthrizationForRoles($securityFunctionsId, $roleId)
    {
        $SecurityRoleFunctionsTbl = TableRegistry::get('security_role_functions');
        $SecurityRoleFunctionsTblData = $SecurityRoleFunctionsTbl->find()->where([
                                            $SecurityRoleFunctionsTbl->aliasField('security_role_id IN') => $roleId,
                                            $SecurityRoleFunctionsTbl->aliasField('security_function_id IN') => $securityFunctionsId,
                                            $SecurityRoleFunctionsTbl->aliasField('_view') => 1
                                        ])->toArray();
        $flag = 0;
        if(!empty($SecurityRoleFunctionsTblData)){
            $dataArray = [];
            foreach ($SecurityRoleFunctionsTblData as $key => $value) {
                if($value->_view == 1){ $dataArray[] = $value->_view; }
            }
            $flag = count($dataArray);
        }
        return $flag;
    }//POCOR-7534 Ends

    public function beforeRender(Event $event)
    {
        parent::beforeRender($event);
        $this->initializeToolbars();
    }

    public function onRenderBinary(Event $event, Entity $entity, PageElement $element)
    {
        $attributes = $element->getAttributes();
        $type = isset($attributes['type']) ? $attributes['type'] : 'binary';
        $fileNameField = isset($attributes['fileNameField']) ? $attributes['fileNameField'] : 'file_name';
        $fileContentField = $element->getKey();
        if ($type == 'image') {
            if ($this->request->param('_ext') == 'json') {
                $primaryKey = $entity->primaryKey;
                $source = isset($attributes['source']) ? $attributes['source'] : $entity->source();
                if (isset($attributes['keyField'])) {
                    $key = TableRegistry::get($source)->primaryKey();
                    if (!is_array($key)) {
                        $primaryKey = $this->encode([$key => $entity->{$attributes['keyField']}]);
                    }
                }
                if ($entity->{$fileContentField}) {
                    return Router::url([
                        'plugin' => null,
                        '_method' => 'GET',
                        'version' => 'v2',
                        'model' => $source,
                        'controller' => 'Restful',
                        'action' => 'image',
                        'id' => $primaryKey,
                        'fileName' => $fileNameField,
                        'fileContent' => $fileContentField,
                        '_ext' => 'json'
                    ], true);
                }
            } else {
                switch ($this->request->param('action')) {
                    case 'view':
                        $fileName = $entity->{$fileNameField};
                        $pathInfo = pathinfo($fileName);
                        if ($entity->{$fileContentField}) {
                            $file = stream_get_contents($entity->{$fileContentField});
                            rewind($entity->{$fileContentField});
                            $entity->{$fileNameField} = 'data:'.$this->response->getMimeType($pathInfo['extension']).';base64,'. base64_encode($file);
                            return $entity->{$fileNameField};
                        }
                        break;
                    case 'index':
                        $primaryKey = $entity->primaryKey;
                        $source = isset($attributes['source']) ? $attributes['source'] : $entity->source();
                        if (isset($attributes['keyField'])) {
                            $key = TableRegistry::get($source)->primaryKey();
                            if (!is_array($key)) {
                                $primaryKey = $this->encode([$key => $entity->{$attributes['keyField']}]);
                            }
                        }
                        if ($entity->{$fileContentField}) {
                            return Router::url([
                                'plugin' => null,
                                '_method' => 'GET',
                                'version' => 'v2',
                                'model' => $source,
                                'controller' => 'Restful',
                                'action' => 'image',
                                'id' => $primaryKey,
                                'fileName' => $fileNameField,
                                'fileContent' => $fileContentField,
                                '_ext' => 'json'
                            ], true);
                        }
                        break;
                    case 'edit':
                    case 'delete':
                        $fileName = $entity->{$fileNameField};
                        $pathInfo = pathinfo($fileName);
                        if ($entity->{$fileContentField}) {
                            if (is_resource($entity->{$fileContentField})) {
                                $file = stream_get_contents($entity->{$fileContentField});
                            } else {
                                $file = $entity->{$fileContentField};
                            }

                            $returnValue = [
                                'extension' => $pathInfo['extension'],
                                'filename' => $fileName,
                                'src' => 'data:'.$this->response->getMimeType($pathInfo['extension']).';base64,'. base64_encode($file)
                            ];

                            rewind($entity->{$fileContentField});
                            return $returnValue;
                        }
                        break;
                }
            }
        } else {
            switch ($this->request->param('action')) {
                case 'view':
                    $primaryKey = $entity->primaryKey;
                    $source = isset($attributes['source']) ? $attributes['source'] : $entity->source();
                    if (isset($attributes['keyField'])) {
                        $key = TableRegistry::get($source)->primaryKey();
                        if (!is_array($key)) {
                            $primaryKey = $this->encode([$key => $entity->{$attributes['keyField']}]);
                        }
                    }
                    $fileName = $entity->{$fileNameField};
                    $element->setAttributes('file_name', $fileName);
                    if ($entity->{$fileContentField}) {
                        return Router::url([
                            'plugin' => null,
                            '_method' => 'GET',
                            'version' => 'v2',
                            'model' => $source,
                            'controller' => 'Restful',
                            'action' => 'download',
                            'id' => $primaryKey,
                            'fileName' => $fileNameField,
                            'fileContent' => $fileContentField,
                            '_ext' => 'json'
                        ], true);
                    }
                    break;
            }
        }
    }

    private function initializeToolbars()
    {
        $request = $this->request;
        $currentAction = $request->action;

        $page = $this->Page;
        $data = $page->getData();

        $actions = $page->getActions();
        $disabledActions = [];
        foreach ($actions as $action => $value) {
            if ($value == false) {
                $disabledActions[] = $action;
            }
        }

        switch ($currentAction) {
            case 'index':
                if (!in_array('add', $disabledActions)) {
                    $page->addToolbar('add', [
                        'type' => 'element',
                        'element' => 'Page.button',
                        'data' => [
                            'title' => __('Add'),
                            'url' => ['action' => 'add'],
                            'iconClass' => 'fa kd-add',
                            'linkOptions' => ['title' => __('Add')]
                        ],
                        'options' => []
                    ]);
                }
                if (!in_array('search', $disabledActions)) {
                    $page->addToolbar('search', [
                        'type' => 'element',
                        'element' => 'Page.search',
                        'data' => [],
                        'options' => []
                    ]);
                }

                break;
            case 'view':
                $primaryKey = !is_array($data) ? $data->primaryKey : $data['primaryKey']; // $data may be Entity or array

                $page->addToolbar('back', [
                    'type' => 'element',
                    'element' => 'Page.button',
                    'data' => [
                        'title' => __('Back'),
                        'url' => ['action' => 'index'],
                        'urlParams' => 'QUERY',
                        'iconClass' => 'fa kd-back',
                        'linkOptions' => ['title' => __('Back'), 'id' => 'btn-back']
                    ],
                    'options' => []
                ]);

                if (!in_array('edit', $disabledActions)) {
                    $page->addToolbar('edit', [
                        'type' => 'element',
                        'element' => 'Page.button',
                        'data' => [
                            'title' => __('Edit'),
                            'url' => ['action' => 'edit', $primaryKey],
                            'iconClass' => 'fa kd-edit',
                            'linkOptions' => ['title' => __('Edit')]
                        ],
                        'options' => []
                    ]);
                }

                if (!in_array('delete', $disabledActions)) {
                    $page->addToolbar('remove', [
                        'type' => 'element',
                        'element' => 'Page.button',
                        'data' => [
                            'title' => __('Delete'),
                            'url' => ['action' => 'delete', $primaryKey],
                            'iconClass' => 'fa kd-trash',
                            'linkOptions' => ['title' => __('Delete')]
                        ],
                        'options' => []
                    ]);
                }
                break;
            case 'add':
                $page->addToolbar('back', [
                    'type' => 'element',
                    'element' => 'Page.button',
                    'data' => [
                        'title' => __('Back'),
                        'url' => ['action' => 'index'],
                        'urlParams' => 'QUERY',
                        'iconClass' => 'fa kd-back',
                        'linkOptions' => ['title' => __('Back'), 'id' => 'btn-back']
                    ],
                    'options' => []
                ]);
                break;
            case 'edit':
                $primaryKey = !is_array($data) ? $data->primaryKey : $data['primaryKey']; // $data may be Entity or array

                $page->addToolbar('view', [
                    'type' => 'element',
                    'element' => 'Page.button',
                    'data' => [
                        'title' => __('View'),
                        'url' => ['action' => 'view', $primaryKey],
                        'iconClass' => 'fa kd-back',
                        'linkOptions' => ['title' => __('Back'), 'id' => 'btn-back']
                    ],
                    'options' => []
                ]);

                $page->addToolbar('list', [
                    'type' => 'element',
                    'element' => 'Page.button',
                    'data' => [
                        'title' => __('List'),
                        'url' => ['action' => 'index'],
                        'urlParams' => 'QUERY',
                        'iconClass' => 'fa kd-lists',
                        'linkOptions' => ['title' => __('List')]
                    ],
                    'options' => []
                ]);
                break;
            case 'delete':
                $primaryKey = !is_array($data) ? $data->primaryKey : $data['primaryKey']; // $data may be Entity or array

                $page->addToolbar('view', [
                    'type' => 'element',
                    'element' => 'Page.button',
                    'data' => [
                        'title' => __('Back'),
                        'url' => ['action' => 'view', $primaryKey],
                        'iconClass' => 'fa kd-back',
                        'linkOptions' => ['title' => __('Back'), 'id' => 'btn-back']
                    ],
                    'options' => []
                ]);

                $page->addToolbar('list', [
                    'type' => 'element',
                    'element' => 'Page.button',
                    'data' => [
                        'title' => __('List'),
                        'url' => ['action' => 'index'],
                        'urlParams' => 'QUERY',
                        'iconClass' => 'fa kd-lists',
                        'linkOptions' => ['title' => __('List')]
                    ],
                    'options' => []
                ]);
                break;
            
            default:
                break;
        }
    }
}
