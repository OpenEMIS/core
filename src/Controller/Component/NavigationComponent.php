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
		$id = $this->request->param('id');

		$navigations = [];

		if ($controller->name == 'Institutions' && $action == 'index') {
			$navigations = [
				'collapse' => false,
				'items' => [
					'Institutions' => [
						'collapse' => true,
						'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']
					],
					'Students' => [
						'collapse' => true,
						'url' => ['plugin' => false, 'controller' => 'Students', 'action' => 'index']
					],
					'Areas' => [
						'collapse' => true,
						'url' => ['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'index']
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
							'Overview' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'view', $id]],
							'Attachments' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'attachments']]
						]
					]
				]
			];
		}

		$controller->set('_navigations', $navigations);
	}
}
