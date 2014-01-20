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

class ControllerActionBehavior extends ModelBehavior {
	public function beforeAction(Model $model, $controller, $action) {
		
	}
	
	public function afterAction(Model $model, $controller, $action) {
		
	}
	
	public function processAction(Model $model, $controller, $action, $name, $plugin = null) {
		if(CakeSession::check('Auth.User') == false) {
			$controller->redirect($controller->Auth->loginAction);
		}
		$controller->autoRender = false;
		$model->beforeAction($controller, $action, $name);
		$result = call_user_func_array(array($model, $action), array($controller, $controller->params));
		$model->afterAction($controller, $action, $name);
		$ctp = substr($action, strlen($name)-1);
		if(empty($ctp)) {
			$ctp = 'index';
		}
		if(!is_null($plugin)) {
			$name = $plugin . '/' . $name;
		}
		if($model->render === true) {
			$controller->render($name . '/' . $ctp);
		} else {
			if($model->render !== false) {
				$controller->render($name . '/' . $model->render);
			}
		}
		
		return $result;
	}
}