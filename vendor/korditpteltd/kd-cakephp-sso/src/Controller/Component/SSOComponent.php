<?php
namespace SSO\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;
use Cake\I18n\Time;
use SSO\ProcessToken;
use Cake\Event\Event;

class SSOComponent extends Component {
    private $controller;
    private $authType = 'Local';

    protected $_defaultConfig = [
        'excludedAuthType' => [],
        'homePageURL' => null,
        'loginPageURL' => null,
        'loginAction' => 'login',
        'cookieAuth' => [
            'username' => 'openemis_no',
            'enabled' => true
        ],
        'restful' => false,
        'cookie' => [
            'name' => 'CookieAuth',
            'path' => '/',
            'expires' => '+2 weeks',
            'domain' => '',
            'encryption' => false
        ],
        'userModel' => 'Users',
        'statusField' => 'status'
    ];

    // Is called before the controller's beforeFilter method.
    public function initialize(array $config) {
        $controller = $this->_registry->getController();
        $this->controller = $controller;
        $this->session = $this->request->session();
        $this->controller->loadComponent('SSO.LocalAuth', $this->_config);
        $this->controller->loadComponent('SSO.GoogleAuth', $this->_config);
        // $this->controller->loadComponent('SSO.Saml2Auth', $this->_config);
        // $this->controller->loadComponent('SSO.OAuth2OpenIDConnectAuth', $this->_config);
    }

    public function getAuthenticationType()
    {
        return $this->authType;
    }

    public function doAuthentication() {

        $this->controller->GoogleAuth->idpLogin();
        $extra = new ArrayObject([]);
        // $this->controller->dispatchEvent('Controller.Auth.beforeAuthenticate', [$extra], $this);

        $event = $this->controller->dispatchEvent('Controller.Auth.authenticate', [$extra], $this);
        if ($event->result) {
            $this->controller->dispatchEvent('Controller.Auth.afterAuthenticate', [$extra], $this);
            $event = $this->controller->dispatchEvent('Controller.Auth.beforeRedirection', [$extra], $this);
            if (!$event->result) {
                $this->controller->redirect($this->_config['homePageURL']);
            }

        } else {
            $this->controller->Auth->logout();
            $this->controller->redirect($this->_config['homePageURL']);
        }
    }
}
