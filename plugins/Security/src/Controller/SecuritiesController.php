<?php

namespace Security\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class SecuritiesController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->ControllerAction->models = [
            'Accounts'      => ['className' => 'Security.Accounts', 'actions' => ['view', 'edit']],
            'Users'             => ['className' => 'Security.Users'],
           // 'SystemGroups'  => ['className' => 'Security.SystemGroups', 'actions' => ['!add', '!edit', '!remove']]
        ];
        $this->attachAngularModules();
    }

    // CAv4
    public function Roles()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Security.SecurityRoles']);
    }
    // end

    public function Users()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Security.Users']);
    }

    public function Permissions($subaction = 'index', $roleId = null)
    {
        if ($subaction == 'edit') {
            $indexUrl = [
                'plugin' => 'Security',
                'controller' => 'Securities',
                'action' => 'Permissions'
            ];
            $viewUrl = [
                'plugin' => 'Security',
                'controller' => 'Securities',
                'action' => 'Permissions',
                'index',
                $roleId
            ];

            $alertUrl = [
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'setAlert'
            ];
            $moduleKey = is_null($this->request->getQuery('module')) ? '' : $this->request->getQuery('module'); //POCOR-8074
            $this->set('roleId', $this->ControllerAction->paramsDecode($roleId)['id']);
            $this->set('indexUrl', $indexUrl);
            $this->set('viewUrl', $viewUrl);
            $this->set('alertUrl', $alertUrl);
            $this->set('moduleKey', $moduleKey);
            $header = __('Security') . ' - ' . TableRegistry::getTableLocator()->get('Security.SecurityRoles')->get($this->ControllerAction->paramsDecode($roleId))->name;
            $this->set('contentHeader', __($header));
            $this->render('Permissions/permission_edit');
        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Security.Permissions']);
        }
    }

    public function UserGroups()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Security.UserGroups']);
    }

    public function SystemGroups()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Security.SystemGroups']);
    }

    public function UserGroupsList()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Security.UserGroupsList']);
    }

    public function SystemGroupsList()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Security.SystemGroupsList']);
    }

    public function RefreshToken()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Security.RefreshTokens']);
    }

    private function attachAngularModules()
    {
        $action = $this->request->getParam('action');
        switch ($action) {
            case 'Permissions':
                if (isset($this->request->getParam('pass')[0])) {
                    if ($this->request->getParam('pass')[0] == 'edit') {
                        $this->Angular->addModules([
                            'alert.svc',
                            'security.permission.edit.ctrl',
                            'security.permission.edit.svc'
                        ]);
                    }
                }
                break;
        }
    }

    public function beforeFilter(EventInterface $event)
    {
        if ($this->getPlugin() == 'Security') {
            $this->Security->setConfig('validatePost', false);
        }
        parent::beforeFilter($event);
        $header = 'Security';
        $this->Navigation->addCrumb($header, ['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'index']);
        $this->Navigation->addCrumb($this->request->getParam('action'));

        $this->set('contentHeader', __($header));
    }

    public function onInitialize(EventInterface $event, Table $model, ArrayObject $extra)
    {
        //change header in POCOR-7175
        if($model->getAlias() =='SystemGroupsList') {
             $header = __('System Groups');
            $listId = $this->request->getQuery('userGroupId');
            $table= TableRegistry::getTableLocator()->get('Security.SecurityGroups');
            $headerName = $table->find()->where(['id' => $listId])->first()->name;
            $header .= ' - ' . __($model->getHeader($headerName));
            $this->set('contentHeader', $header);
        }elseif($model->getAlias() == 'UserGroupsList') {
            $header = __('User Groups');
            $listId = $this->request->getQuery('userGroupId');
            $table= TableRegistry::getTableLocator()->get('Security.UserGroups');
            $headerName = $table->find()->where(['id IS ' => $listId])->first()->name;
            $header .= ' - ' . __($model->getHeader($headerName));
            $this->set('contentHeader', $header);
        }else {
             $header = __('Security');
             $header .= ' - ' . __($model->getHeader($model->getAlias()));
             $this->set('contentHeader', $header);
        }
    }

    public function index()
    {
        return $this->redirect(['action' => 'Users']);
    }

    /**
     * POCOR-9370
     * Generate the tab elements for the user view.
     *
     * Depending on the query parameter "super", this method dynamically builds
     * the tab navigation. If "super" equals 1, only the **Account** tab is shown. 
     * Otherwise, both the **Details** and **Account** tabs are included.
     * The final set of tabs is filtered through the tab permission check before returning.
     *
     * @param array $options Optional parameters (may contain 'id').
     * @return array Tab elements after permission check.
     */
    public function getUserTabElements($options = [])
    {
        $plugin = $this->getPlugin();
        $name = $this->getName();
        $id = isset($options['id']) ? $options['id'] : $this->request->getSession()->read($name . '.id');
       
        $tabElements = [];
        $currentUser = $this->Auth->user(); 
        $Users = TableRegistry::getTableLocator()->get('Security.Users');
        $checkUser = $Users->find()->where(['id' => $id])->first();
        if ($checkUser && $checkUser->super_admin === 1) { 
            $tabElements['Accounts'] = [
                'url' => [
                    'plugin' => $plugin,
                    'controller' => $name,
                    'action' => 'Accounts',
                    'view',
                    $this->ControllerAction->paramsEncode(['id' => $id])
                ],
                'text' => __('Account')
            ];
        } else {
            $tabElements[$name] = [
                'url' => [
                    'plugin' => $plugin,
                    'controller' => $name,
                    'action' => 'Users',
                    'view',
                    $this->ControllerAction->paramsEncode(['id' => $id])
                ],
                'text' => __('Details')
            ];
            $tabElements['Accounts'] = [
                'url' => [
                    'plugin' => $plugin,
                    'controller' => $name,
                    'action' => 'Accounts',
                    'view',
                    $this->ControllerAction->paramsEncode(['id' => $id])
                ],
                'text' => __('Account')
            ];
        }

        return $this->TabPermission->checkTabPermission($tabElements);
    }


    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->addHelper('ControllerAction.ControllerAction');
    }

}
