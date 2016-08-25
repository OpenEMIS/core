<?php
namespace SSO\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;
use Cake\I18n\Time;
use SSO\ProcessToken;

class SSOComponent extends Component {
	private $controller;
    private $authType = 'Local';

	protected $_defaultConfig = [
		'homePageURL' => null,
		'loginPageURL' => null,
		'restful' => false,
        'cookie' => [
            'name' => 'CookieAuth',
            'path' => '/',
            'expires' => '+2 weeks',
            'domain' => '',
            'encryption' => false
        ],
        'userModel' => 'Users'
	];

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.initialize'] = ['callable' => 'beforeFilter', 'priority' => 11];
        return $events;
    }

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

        if ($authType != 'Local') {
           $this->controller->Auth->config('authenticate', [
                'SSO.Cookie' => [
                    'userModel' => $this->_config['userModel'],
                    'fields' => [
                        'username' => 'openemis_no'
                    ],
                    'cookie' => [
                        'name' => $this->_config['cookie']['name'],
                        'path' => $this->_config['cookie']['path'],
                        'expires' => $this->_config['cookie']['expires'],
                        'domain' => $this->_config['cookie']['domain'],
                        'encryption' => $this->_config['cookie']['encryption']
                    ]
                ]
            ]);
        }


		$this->controller->loadComponent($type, $this->_config);
	}

    public function beforeFilter($event) {
        $user = $this->controller->Auth->identify();
        if ($user) {
            $this->controller->Auth->setUser($user);
        }
    }

    public function doAuthentication() {
    	$extra = new ArrayObject([]);
    	// $this->controller->dispatchEvent('Controller.Auth.beforeAuthenticate', [$extra], $this);

    	$event = $this->controller->dispatchEvent('Controller.Auth.authenticate', [$extra], $this);
    	if ($event->result) {
            $this->controller->dispatchEvent('Controller.Auth.afterAuthenticate', [$extra], $this);

            if ($this->authType != 'Local') {
               // Set of cookie
                $now = Time::now();
                $now->modify('+2 weeks');
                $cookieConfig = $this->_config['cookie'];
                if (!empty($cookieConfig['domain'])) {
                    $cookieConfig['domain'] = '.' . $cookieConfig['domain'];
                }
                $cookieName = $cookieConfig['name'];
                unset($cookieConfig['name']);
                $this->controller->Cookie->configKey($cookieName, $cookieConfig);
                $user = $this->controller->Auth->user();
                $token = ProcessToken::generateToken($user, $now->toUnixString());
                $this->controller->Cookie->write($cookieName, $token);
            }

			return $this->controller->redirect($this->_config['homePageURL']);
    	} else {
    		$this->controller->Auth->logout();
    		return $this->controller->redirect($this->_config['homePageURL']);
    	}
    }
}
