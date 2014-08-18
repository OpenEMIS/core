<?php
/*
@OPENEMIS SCHOOL LICENSE LAST UPDATED ON 2014-01-30

OpenEMIS School
Open School Management Information System

Copyright © 2014 KORD IT. This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation, 
either version 3 of the License, or any later version. This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please email contact@openemis.org.
*/

class ControllerAction2Behavior extends ModelBehavior {
	public function beforeAction(Model $model) {
		$model->getFields($model);
		$model->Message = $model->controller->Message;
		$model->Session = $model->controller->Session;
		$model->Navigation = $model->controller->Navigation;
		$model->request = $model->controller->request;
		$model->controller->set('model', $model->alias);
	}
	
	public function afterAction(Model $model) {
		$model->controller->set('fields', $model->fields);
	}
	
	public function processAction(Model $model, $controller) {
		if (CakeSession::check('Auth.User') == false) {
			$controller->redirect($controller->Auth->loginAction);
		}
		$model->controller = $controller;
		$controller->autoRender = false;
		$params = $controller->request->params;
		$action = 'index';
		$plugin = $params['plugin'];
		
		if (!empty($params['pass'])) {
			$action = array_shift($params['pass']);
		}
		$model->action = $action;
		$controller->set('action', $action);
		$module = $controller->action;
		$model->beforeAction();
		$result = call_user_func_array(array($model, $action), $params['pass']);
		$model->afterAction();
		
		if (!is_null($plugin)) {
			//$name = $plugin . '/' . $name;
		}
		
		if ($model->render === 'auto') {
			if ($action == 'add' || $action == 'edit') {
				$controller->render('../Elements/templates/edit');
			} else if ($action == 'view') {
				$controller->render('../Elements/templates/view');
			}
		} else if ($model->render === true) {
			$controller->render($module . '/' . $action);
		} else if ($model->render === 'override') {
			$controller->render($model->render_override );
		} else {
			if ($model->render !== false) {
				$controller->render($module . '/' . $model->render);
			}
		}
		return $result;
	}
	
	public function view(Model $model, $id=0) {
		$model->render = 'auto';
		if ($model->exists($id)) {
			$model->recursive = 0;
			$data = $model->findById($id);
			$model->Session->write($model->alias.'.id', $id);
			$model->setVar(compact('data'));
		} else {
			$model->Message->alert('general.view.notExists');
			return $model->redirect(array('action' => get_class($model)));
		}
	}
	
	public function add(Model $model) {
		$model->render = 'auto';
		if ($model->request->is(array('post', 'put'))) {
			$model->create();
			if ($model->save($model->request->data)) {
				$model->Message->alert('general.add.success');
				return $model->redirect(array('action' => get_class($model)));
			} else {
				$model->Message->alert('general.add.failed');
			}
		}
	}
	
	public function edit(Model $model, $id=0) {
		$model->render = 'auto';
		if ($model->exists($id)) {
			$this->recursive = 0;
			$data = $model->findById($id);
			
			if ($model->request->is(array('post', 'put'))) {
				if ($model->save($model->request->data)) {
					$model->Message->alert('general.edit.success');
					return $model->redirect(array('action' => get_class($model), 'view', $id));
				} else {
					$model->Message->alert('general.edit.failed');
				}
			} else {
				$model->request->data = $data;
			}
		} else {
			$model->Message->alert('general.view.notExists');
			return $model->redirect(array('action' => get_class($model)));
		}
	}
	
	public function remove(Model $model) {
		if ($model->Session->check($model->alias . '.id')) {
			$id = $model->Session->read($model->alias . '.id');
			if($model->delete($id)) {
				$model->Message->alert('general.delete.success');
			} else {
				$model->Message->alert('general.delete.failed');
			}
			$model->Session->delete($model->alias . '.id');
			return $model->redirect(array('action' => get_class($model)));
		}
	}
	
	public function redirect(Model $model, $url) {
		return $model->controller->redirect($url);
	}
	
	public function setVar(Model $model, $name, $value=null) {
		if (!is_null($name) && !is_null($value)) {
			$model->controller->set($name, $value);
		} else if (is_array($name)) {
			$model->controller->set($name);
		}
	}
}