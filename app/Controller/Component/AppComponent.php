<?php
class AppComponent extends Component {
	public $render = true;
	
	public function beforeAction($controller, $action) {
		
	}
	
	public function afterAction($controller, $action) {
		
	}
	
	public function processAction($controller, $action, $plugin = null) {
		$action = Inflector::underscore($action);
		
		if(CakeSession::check('Auth.User') == false) {
			$controller->redirect($controller->Auth->loginAction);
		}
		$controller->autoRender = false;
		if(empty($action)) {
			$action = 'index';
		}
		$this->beforeAction($controller, $action);
		$result = call_user_func_array(array($this, $action), array($controller, $controller->params));
		$this->afterAction($controller, $action);
		$name = substr(get_class($this), 0, strpos(get_class($this), 'Component'));
		$name = Inflector::underscore($name);
		if(!is_null($plugin)) {
			$name = $plugin . '/' . $name;
		}
		if($this->render === true) {
			$controller->render($name . '/' . $action);
		} else {
			if($this->render !== false) {
				$controller->render($name . '/' . $this->render);
			}
		}
		return $result;
	}
}
