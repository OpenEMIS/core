<?php
namespace App\Controller\Component;

use ArrayObject;
use Cake\I18n\Time;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

class WorkBenchComponent extends Component {
	private $controller;
	private $action;
	private $Session;

	public $components = ['Auth', 'AccessControl'];

	public function initialize(array $config) {
	}

	public function getList() {
		$models = $this->config('models');

		$data = new ArrayObject([]);
		foreach ($models as $model) {
			// trigger event for getList to each model
			$subject = TableRegistry::get($model);

			$eventMap = $subject->implementedEvents();
			$params = [$this->AccessControl, $data];
			$event = new Event('Workbench.Model.onGetList', $this, $params);
			$subject->eventManager()->dispatch($event);
			if ($event->isStopped()) { return $event->result; }
		}

		return $data;
	}
}
