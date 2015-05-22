<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;

class NavigationComponent extends Component {
	// Is called after the controller executes the requested action’s logic, but before the controller’s renders views and layout.
	public $controller;
	public $action;

	public function initialize(array $config) {
		$this->controller = $this->_registry->getController();
		$this->action = $this->request->params['action'];
	}

	public function beforeRender(Event $event) {
		$controller = $this->controller;
		$action = $this->action;

		$navigations = [];

		if ($controller->name == 'Institutions' && $action == 'index') {
			$navigations = [
				'collapse' => false,
				'items' => [
					'Institutions' => [
						'collapse' => true,
						'url' => ['plugin' => false, 'controller' => 'Institutions', 'action' => 'index']
					],
					'Students' => [
						'collapse' => true,
						'url' => ['plugin' => false, 'controller' => 'Students', 'action' => 'index']
					]
				]
			];
		} else {
			$navigations = [
				'collapse' => false,
				'items' => [
					'Institutions' => [
						'collapse' => true,
						'url' => ['plugin' => false, 'controller' => 'Institutions', 'action' => 'index'],
						'items' => [
							'Overview' => ['url' => ['plugin' => false, 'controller' => 'Institutions', 'action' => 'view']]
						]
					]
				]
			];
		}

		$controller->set('_navigations', $navigations);
	}
}
