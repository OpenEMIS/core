<?php
namespace App\Controller\Component;

use Exception;
use ArrayObject;
use Cake\I18n\Time;
use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

class WorkBenchComponent extends Component {
	private $controller;
	private $action;
	private $Session;

	// Components are defined in the parent class as protected $components = []
	// We set them in initialize() method instead to avoid type declaration conflicts

	public function initialize(array $config) {
		// Set components to avoid redeclaring the property (which causes type conflicts in CakePHP 5)
		$this->components = ['Auth', 'AccessControl', 'Workflow'];
		
		// Manually populate _componentMap since we set components after constructor
		// This is needed for __get() to work properly in CakePHP 5
		if ($this->components) {
			$this->_componentMap = $this->_registry->normalizeArray($this->components);
		}
	}
}
