<?php
namespace App\Controller;

use Cake\Event\Event;
use DateTime;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use SSO\Controller\LoginController as Controller;

class LoginController extends Controller
{
    private $sso = false;
    private $productName = 'OpenEmis Core';
    public function initialize()
    {
        parent::initialize();

        $this->loadComponent('Auth', [
            'loginAction' => [
                'plugin' => 'User',
                'controller' => 'Users',
                'action' => 'login'
            ],
            'logoutRedirect' => [
                'plugin' => null,
                'controller' => 'Login',
                'action' => 'login'
            ]
        ]);
        $this->loadComponent('OpenEmis.OpenEmis', [
            'homeUrl' => ['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index'],
            'headerMenu' => [
                'Preferences' => [
                    'url' => ['plugin' => false, 'controller' => 'Preferences', 'action' => 'index']
                ]
            ],
            'productName' => $this->productName,
            'theme' => 'core'
        ]);

        $this->Auth->allow('login');
        $this->loadComponent('Localization.Localization', [
            'productName' => $this->productName
        ]);
    }

    public function beforeFilter(Event $event)
    {
        $ssoType = TableRegistry::get('Configuration.ConfigItems')->value('authentication_type');
        $this->sso = $ssoType != 'Local';
        $this->set('_sso', $this->sso);
    }

    public function login()
    {
        $this->viewBuilder()->layout(false);
        if ($this->sso) {
            parent::login();
        }
        if ($this->Auth->user()) {
            return $this->redirect(['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index']);
        }
        $username = '';
        $password = '';
        $session = $this->request->session();

        $this->set('username', $username);
        $this->set('password', $password);
    }
}
