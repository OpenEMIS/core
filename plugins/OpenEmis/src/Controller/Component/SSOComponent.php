<?php
namespace OpenEmis\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\ORM\TableRegistry;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Utility\Security;
use Firebase\JWT\JWT;
use Cake\Network\Exception\BadRequestException;

class SSOComponent extends Component {
	private $controller;

	protected $_defaultConfig = [
		'homePageURL' => null,
		'loginPageURL' => null,
		'restful' => false
	];

	// Is called before the controller's beforeFilter method.
	public function initialize(array $config) {
		$controller = $this->_registry->getController();
		$this->controller = $controller;
		
		$ConfigItems = TableRegistry::get('ConfigItems');
		$authType = $ConfigItems->value('authentication_type');
		if (empty($authType)) {
			$authType = 'Local';
		}
		$type = 'OpenEmis.' . ucfirst($authType) . 'Auth';
		$this->controller->loadComponent($type, $this->_config);
		$this->controller->loadComponent('Cookie');
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
        // $events
        return $events;
    }

    public function doAuthentication() {
    	$extra = new ArrayObject([]);
    	// $this->controller->dispatchEvent('Controller.Auth.beforeAuthenticate', [$extra], $this);

    	$event = $this->controller->dispatchEvent('Controller.Auth.authenticate', [$extra], $this);
    	if ($event->result) {
    		$restfulCall = false;
    		if ($this->controller->Cookie->check('Restful.Call')) {
    			$restfulCall = $this->controller->Cookie->read('Restful.Call');
    		}
    		if ($restfulCall) {
    			return $this->controller->redirect(['plugin' => null, 'controller' => 'Rest', 'action' => 'auth', 'payload' => $this->generateToken(), 'version' => '2.0']);
    		} else {
    			return $this->controller->redirect($this->_config['homePageURL']);
    		}
    	} else {
    		$this->controller->Auth->logout();
    		return $this->controller->redirect($this->_config['homePageURL']);
    	}

    	// $this->controller->dispatchEvent('Controller.Auth.afterAuthenticate', [$extra], $this);
    }

    public function generateToken() {
    	$user = $this->controller->Auth->user();

    	// Expiry change to 24 hours
	    return JWT::encode([
	                'sub' => $user['id'],
	                'exp' =>  time() + 86400
	            ],
	            Security::salt());
    }
    
}
