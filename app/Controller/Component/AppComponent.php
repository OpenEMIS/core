<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

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
			$action = strtolower($action);
			$controller->render($name . '/' . $action);
		} else {
			if($this->render !== false) {
				$controller->render($name . '/' . $this->render);
			}
		}
		return $result;
	}
}
