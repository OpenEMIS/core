<?php
App::uses('Sanitize', 'Utility');

class ActivityComponent extends Component {
	private $controller;
	public $model;
	public $components = array('Navigation', 'Session', 'Message');
	
	// Is called before the controller's beforeFilter method.
	public function initialize(Controller $controller) {
		$this->controller = $controller;
		$this->model = $this->settings['model'];
	}
	
	// Is called after the controller's beforeFilter method but before the controller executes the current action handler.
	public function startup(Controller $controller) {
		if ($controller->action == 'history') {
			$controller->request->params['action'] = 'ComponentAction';
			$this->activity();
		}
	}
	
	// Is called after the controller executes the requested action’s logic, but before the controller’s renders views and layout.
	public function beforeRender(Controller $controller) {
	}

	public function activity() {
		$this->Navigation->addCrumb('History');
		$model = $this->model;

		// $id = $this->Session->read('InstitutionSite.id');
		// $conditions = array("$model.institution_site_id" => $id);
		$conditions = $this->controller->{$model}->getConditions();
		
		$this->controller->{$model}->contain('ModifiedUser');

		$order = empty($this->controller->params->named['sort']) ? array("$model.created" => 'desc') : array();
		$data = $this->controller->Search->search($this->controller->{$model}, $conditions, $order);
		
		if (empty($data)) {
			$this->Message->alert('general.noData');
		}
		$this->controller->set(compact('data', 'model'));
		$this->controller->render('/Elements/templates/activity');
	}
}
