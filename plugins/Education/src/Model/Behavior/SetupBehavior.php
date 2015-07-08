<?php
namespace Education\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class SetupBehavior extends Behavior {
	private $setups = ['subjects', 'certifications', 'field_of_studies', 'programme_orientations'];

	protected $_defaultConfig = [
		'events' => [
			'ControllerAction.Model.index.beforeAction' => 'indexBeforeAction'
		]
	];

	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events = array_merge($events, $this->config('events'));
    	return $events;
	}

	public function indexBeforeAction(Event $event) {
		$controller = $this->_table->controller;

		// Add controls to index page
		$toolbarElements = [
            ['name' => 'Education.controls', 'data' => [], 'options' => []]
        ];
		$controller->set('toolbarElements', $toolbarElements);
		// End

		// Get page options and their key
		$setupId = $controller->request->query('setup');
		$setupOptions = [];
		foreach ($this->setups as $setup) {
			$setupOptions[] = __(Inflector::humanize($setup));
		}
		$selectedSetup = !is_null($setupId) ? $setupId : key($setupOptions);

        $controller->set(compact('setupOptions', 'selectedSetup'));
		// End

        $selectedAction = Inflector::camelize($setupOptions[$selectedSetup]);
        if ($this->_table->alias != $selectedAction) {
        	$action = $this->_table->ControllerAction->buttons['index']['url'];
	        $action['action'] = $selectedAction;
	        $controller->redirect($action);
        }
	}
}
