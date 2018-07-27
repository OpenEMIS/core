<?php
namespace Security\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class SecuritiesController extends AppController
{
    public function initialize()
    {
        parent::initialize();

        $this->ControllerAction->models = [
            'Accounts'      => ['className' => 'Security.Accounts', 'actions' => ['view', 'edit']],
            'Users'             => ['className' => 'Security.Users'],
            'SystemGroups'  => ['className' => 'Security.SystemGroups', 'actions' => ['!add', '!edit', '!remove']]
        ];
        $this->attachAngularModules();
    }

    // CAv4
    public function Roles()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Security.SecurityRoles']);
    }
    // end

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
            $moduleKey = is_null($this->request->query('module')) ? '' : $this->request->query('module');
            $this->set('roleId', $this->ControllerAction->paramsDecode($roleId)['id']);
            $this->set('indexUrl', $indexUrl);
            $this->set('viewUrl', $viewUrl);
            $this->set('alertUrl', $alertUrl);
            $this->set('moduleKey', $moduleKey);
            $header = __('Security') . ' - ' . TableRegistry::get('Security.SecurityRoles')->get($this->ControllerAction->paramsDecode($roleId))->name;
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

    public function RefreshToken()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Security.RefreshTokens']);
    }

    private function attachAngularModules()
    {
        $action = $this->request->action;

        switch ($action) {
            case 'Permissions':
                if (isset($this->request->pass[0])) {
                    if ($this->request->param('pass')[0] == 'edit') {
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

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $header = 'Security';
        $this->Navigation->addCrumb($header, ['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'index']);
        $this->Navigation->addCrumb($this->request->action);

        $this->set('contentHeader', __($header));
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Security');
        $header .= ' - ' . __($model->getHeader($model->alias));
        $this->set('contentHeader', $header);
    }

    public function index()
    {
        return $this->redirect(['action' => 'Users']);
    }

    public function getUserTabElements($options = [])
    {
        $plugin = $this->plugin;
        $name = $this->name;

        $id = (array_key_exists('id', $options))? $options['id']: $this->request->session()->read($name.'.id');

        $tabElements = [
            $this->name => [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Users', 'view', $this->ControllerAction->paramsEncode(['id' => $id])],
                'text' => __('Details')
            ],
            'Accounts' => [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Accounts', 'view', $this->ControllerAction->paramsEncode(['id' => $id])],
                'text' => __('Account')
            ]
        ];

        return $this->TabPermission->checkTabPermission($tabElements);
    }
}
