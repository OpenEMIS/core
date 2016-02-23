<?php
namespace OpenEmis\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\ORM\TableRegistry;

class SSOComponent extends Component {
	private $controller;

	protected $_defaultConfig = [
		'homePageURL' => null,
		'loginPageURL' => null,
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
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
        // $events
        return $events;
    }

    public function doAuthentication() {
    	$extra = new ArrayObject([]);

    	// $this->controller->dispatchEvent('Controller.Auth.beforeAuthenticate', [$extra], $this);

		$ext = $this->controller->request->params['_ext'];
    	$event = $this->controller->dispatchEvent('Controller.Auth.authenticate', [$extra], $this);
    	if ($event->result) {
    		if ( !in_array($ext, ['json', 'xml']) ) {
	    		return $this->controller->redirect($this->_config['homePageURL']);
    		}
    	} else {
			$this->controller->Auth->logout();
			if ( !in_array($ext, ['json', 'xml']) ) {
	    		return $this->controller->redirect($this->_config['homePageURL']);
    		}
    	}

    	// $this->controller->dispatchEvent('Controller.Auth.afterAuthenticate', [$extra], $this);
    }
}
