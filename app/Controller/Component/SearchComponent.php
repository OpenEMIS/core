<?php
App::uses('Sanitize', 'Utility');

class SearchComponent extends Component {
	private $controller;
	public $components = array('Session');
	public $pageOptions = array(10, 20, 30, 40, 50);
	
	public function initialize(Controller $controller) {
		$this->controller = $controller;
	}
	
	public function startup(Controller $controller) {
		
	}
	
	public function beforeRender(Controller $controller) {
		
	}

	public function search($model, $conditions = array(), $order = array()) {
		$alias = $model->alias;
		$request = $this->controller->request;
		$limit = $this->Session->check($alias.'.search.limit') ? $this->Session->read($alias.'.search.limit') : key($this->pageOptions);
		$search = $this->Session->check($alias.'.search.key') ? $this->Session->read($alias.'.search.key') : '';
		
		if ($request->is(array('post', 'put'))) {
			$search = Sanitize::escape(trim($request->data[$alias]['search']));

			if (is_callable(array($model, 'getSearchConditions'))) {
				$conditions = array_merge($conditions, $model->getSearchConditions($search));
			} else {
				$schema = $model->schema();
				if (array_key_exists('name', $schema)) {
					$conditions[$alias.'.name LIKE'] = '%' . $search . '%';
				}
			}
			$this->Session->write($alias.'.search.key', $search);
			
			if (strlen($request->data[$alias]['limit']) > 0) {
				$limit = $request->data[$alias]['limit'];
				$this->Session->write($alias.'.search.limit', $limit);
			}
		} else {
			if (is_callable(array($model, 'getSearchConditions'))) {
				$conditions = array_merge($conditions, $model->getSearchConditions($search));
			} else {
				$schema = $model->schema();
				if (array_key_exists('name', $schema)) {
					$conditions[$alias.'.name LIKE'] = '%' . $search . '%';
				}
			}
		}
		$this->controller->request->data[$alias]['search'] = $search;
		$this->controller->request->data[$alias]['limit'] = $limit;
		$this->controller->set('search', $search);
		$this->controller->set('pageOptions', $this->pageOptions);
		$this->controller->Paginator->settings = array('limit' => $this->pageOptions[$limit], 'order' => $order);

		try {
			$data = $this->controller->paginate($alias, $conditions);
		} catch (NotFoundException $e) {
			$this->log($e->getMessage(), 'debug');
			return $this->controller->redirect(array('action' => $this->controller->action));
		}
		return $data;
	}
}
