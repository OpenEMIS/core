<?php
namespace OpenEmis\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\ORM\TableRegistry;

class SSOComponent extends Component {
	private $controller;

	protected $_defaultConfig = [
		'homePageURL' => null,
		'userNotAuthorisedURL' => null,
	];

	// Is called before the controller's beforeFilter method.
	public function initialize(array $config) {
		$controller = $this->_registry->getController();
		$this->controller = $controller;
		
		$ConfigItems = TableRegistry::get('ConfigItems');
		$authType = $ConfigItems->value('authentication_type');

		$type = 'OpenEmis.' . ucfirst($authType) . 'Auth';
		$this->controller->loadComponent($type, $this->_config);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
        // $events
        return $events;
    }

    public function doAuthentication() {
    	$extra = new ArrayObject([]);

    	$this->controller->dispatchEvent('Controller.Auth.beforeAuthenticate', [$extra], $this);

    	$event = $this->controller->dispatchEvent('Controller.Auth.authenticate', [$extra], $this);
    	if ($event->result) {
    		return $this->controller->redirect($this->_config['homePageURL']);
    	} else {
    		return $this->controller->redirect($this->_config['userNotAuthorisedURL']);
    	}

    	$this->controller->dispatchEvent('Controller.Auth.afterAuthenticate', [$extra], $this);
    }
}
