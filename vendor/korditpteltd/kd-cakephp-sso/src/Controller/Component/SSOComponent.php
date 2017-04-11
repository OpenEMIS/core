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

        $ConfigItems = TableRegistry::get('ConfigItems');
        $entity = $ConfigItems->findByCode('authentication_type')->first();
        $authType = strlen($entity->value) ? $entity->value : $entity->default_value;
        if (empty($authType)) {
            $authType = 'Local';
        }
        $this->authType = $authType;
        $type = 'SSO.' . ucfirst($authType) . 'Auth';
        $this->controller->loadComponent('Cookie');
        if (!in_array($authType, $this->_config['excludedAuthType'])) {
            $this->controller->loadComponent($type, $this->_config);
        }
    }

    public function getAuthenticationType()
    {
        return $this->authType;
    }

    public function doAuthentication() {
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
