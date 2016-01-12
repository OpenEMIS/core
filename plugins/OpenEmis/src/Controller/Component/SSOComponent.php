<?php
namespace OpenEmis\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\ORM\TableRegistry;

class SSOComponent extends Component {
	private $controller;

	public $components = ['OpenEmis.LocalAuth'];

	// Is called before the controller's beforeFilter method.
	public function initialize(array $config) {
		$controller = $this->_registry->getController();
		$this->controller = $controller;

		$type = 'local';
		// get the type from system config
		$type = 'OpenEmis.' . ucfirst($type) . 'Auth';

		$ConfigItems = TableRegistry::get('ConfigItems');
		$authType = $ConfigItems->value('auth_type');

		if ($authType != $type) {
			$type = $authType;
			$this->components = [$type];
		}

		foreach ($this->components as $component) {
			if ($component == $type) {
				$this->controller->loadComponent($component);
				break;
			}
		}
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
    		return $this->controller->redirect(['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index']);
    	} else {
    		return $this->controller->redirect(['action' => 'login']);
    	}

    	$this->controller->dispatchEvent('Controller.Auth.afterAuthenticate', [$extra], $this);
    }
}
