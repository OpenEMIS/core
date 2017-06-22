<?php
namespace SSO\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class SSOComponent extends Component
{
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
        'statusField' => 'status',
        'recordKey' => null,
    ];

    // Is called before the controller's beforeFilter method.
    public function initialize(array $config)
    {
        $controller = $this->_registry->getController();
        $this->controller = $controller;
        $this->session = $this->request->session();
    }

    public function getAuthenticationType()
    {
        return $this->authType;
    }

    public function doAuthentication($authenticationType = 'Local', $recordKey = null)
    {
        if ($authenticationType != 'Local') {
            $SystemAuthenticationsTable = TableRegistry::get('SSO.SystemAuthentications');
            $attribute = $SystemAuthenticationsTable->get($recordKey, ['contain' => $authenticationType])->toArray();

            if ($attribute['status']) {
                $authAttribute = $attribute[Inflector::underscore($authenticationType)];
                unset($attribute[strtolower($authenticationType)]);
                $mappingAttribute = $attribute;
                $this->_config['authAttribute'] = $authAttribute;
                $this->_config['mappingAttribute'] = $mappingAttribute;
                $this->_config['recordKey'] = $recordKey;
            } else {
                $authenticationType = 'Local';
            }
        }

        $this->controller->loadComponent('SSO.'.$authenticationType.'Auth', $this->_config);
        $extra = new ArrayObject([]);
        // $this->controller->dispatchEvent('Controller.Auth.beforeAuthenticate', [$extra], $this);
        $event = $this->controller->dispatchEvent('Controller.Auth.authenticate', [$extra], $this);
        if ($event->result) {
            $this->controller->dispatchEvent('Controller.Auth.afterAuthenticate', [$extra], $this);
            $event = $this->controller->dispatchEvent('Controller.Auth.beforeRedirection', [$extra], $this);
            if (!$event->result) {
                $this->controller->redirect($this->_config['homePageURL']);
            }
        }
        $this->controller->redirect($this->_config['homePageURL']);
    }
}
