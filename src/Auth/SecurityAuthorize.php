<?php
namespace App\Auth;

use Cake\Auth\BaseAuthorize;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;

class SecurityAuthorize extends BaseAuthorize {
	public function authorize($user, Request $request) {
		$controller = $this->_registry->getController();
		$action = $request->params['action'];
		$AccessControl = $controller->AccessControl;
		$authorized = false;

		if (!$request->is('ajax')) {

			// Set for roles belonging to the controller
			$roles = [];
			$event = $controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', [], $this);
	    	if ($event->result) {
	    		$roles = $event->result;	
	    	}

			if ($AccessControl->isIgnored($controller->name, $action) || $user['super_admin'] == true) {
				$authorized = true;
			} else if ($action == 'ComponentAction') { // actions from ControllerActionComponent
				$model = $controller->ControllerAction->model();
				$action = $model->action;

				if ($AccessControl->isIgnored($model->registryAlias(), $action)) {
					$authorized = true;
				} else {
					if (array_key_exists($model->alias, $controller->ControllerAction->models)) {
						$authorized = $AccessControl->check([$controller->name, $model->alias, $action], $roles);
					} else {
						$authorized = $AccessControl->check([$controller->name, $action], $roles);
					}
				}
			} else { // normal actions from Controller
				$authorized = $AccessControl->check([$controller->name, $action], $roles);
			}

			if (!$authorized) {
				$controller->Alert->error('security.noAccess');
			}
		} else {
			$authorized = true;
		}
		return $authorized;
	}
}
