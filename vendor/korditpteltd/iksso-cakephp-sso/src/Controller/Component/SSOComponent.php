<?php
namespace SSO\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Http\ServerRequest;

class SSOComponent extends Component
{
    private $controller;
    private $authType = 'Local';
    public $components = ['Auth'];

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
    public function initialize(array $config): void
    {
        $controller = $this->_registry->getController();
        $this->controller = $controller;
        $this->session = $this->getController()->getRequest()->getSession();
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Controller.Auth.afterAuthenticate'] = 'afterAuthenticate';
        return $events;
    }

    public function getAuthenticationType()
    {
        return $this->authType;
    }

    public function doAuthentication($authenticationType = 'Local', $code = null)
    {
        if ($authenticationType != 'Local') {
            $SystemAuthenticationsTable = TableRegistry::get('SSO.SystemAuthentications');
            $attribute = $SystemAuthenticationsTable
                ->find()
                ->contain([$authenticationType])
                ->where([
                    $SystemAuthenticationsTable->aliasField('code') => $code
                ])
                ->enableHydration(false)
                ->first();
            if (!empty($attribute) && $attribute['status']) {
                $authAttribute = $attribute[Inflector::underscore($authenticationType)];
                unset($attribute[strtolower($authenticationType)]);
                $mappingAttribute = $attribute;
                $this->_config['authAttribute'] = $authAttribute;
                $this->_config['mappingAttribute'] = $mappingAttribute;
                $this->_config['recordKey'] = $attribute['id'];
            } else {
                $authenticationType = 'Local';
            }
        }

        $this->controller->loadComponent('SSO.'.$authenticationType.'Auth', $this->_config);
        $extra = new ArrayObject([]);
        // $this->controller->dispatchEvent('Controller.Auth.beforeAuthenticate', [$extra], $this);
        $event = $this->controller->dispatchEvent('Controller.Auth.authenticate', [$extra], $this);
        if ($event->getResult()) {
            $this->controller->dispatchEvent('Controller.Auth.afterAuthenticate', [$extra], $this);
            $event = $this->controller->dispatchEvent('Controller.Auth.beforeRedirection', [$extra], $this);
            if (!$event->getResult()) {
                $this->controller->redirect($this->_config['homePageURL']);
            }
        }
        $this->controller->redirect($this->_config['homePageURL']);
    }

    public function afterAuthenticate(Event $event, ArrayObject $extra)
    {
        $request = $this->getController()->getRequest();
        $user = $this->Auth->user();
        if ($user) {
            $request->trustProxy = true;
            $clientIp = $request->clientIp();
            $sessionId = $request->getSession()->id();
            TableRegistry::getTableLocator()->get('SSO.SecurityUserLogins')->addLoginEntry($user['id'], $clientIp, $sessionId);
        }
    }
}
