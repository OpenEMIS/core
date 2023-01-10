<?php
namespace Education\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use ArrayObject;

class SetupBehavior extends Behavior {
	private $setups = ['stages', 'subjects', 'certifications', 'field_of_studies', 'programme_orientations'];

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

	private function addSetupControl(ArrayObject $extra, $data = []) {
		$extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => $data, 'order' => 2];
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra) {
		$controller = $this->_table->controller;

		// Get page options and their key
		$setupOptions = [];
		$actionOptions = [];
		foreach ($this->setups as $setup) {
			$setupOptions[] = __(Inflector::humanize($setup));
			$actionOptions[] = Inflector::humanize($setup);
		}
		$selectedSetup = $this->_table->queryString('setup', $setupOptions);
		$this->_table->advancedSelectOptions($setupOptions, $selectedSetup);
        // End

		$selectedAction = Inflector::camelize($actionOptions[$selectedSetup]);
		if ($this->_table->alias != $selectedAction) {
	        $action = $this->_table->ControllerAction->url('index');//$this->_table->ControllerAction->buttons['index']['url'];
	        $action['action'] = $selectedAction;
			$controller->redirect($action);
		}

		$this->addSetupControl($extra, ['setupOptions' => $setupOptions]);
	}
}
