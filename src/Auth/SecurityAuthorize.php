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
			if ($AccessControl->isIgnored($controller->name, $action) || $user['super_admin'] == true) {
				$authorized = true;
			} else if ($action == 'ComponentAction') { // actions from ControllerActionComponent
				$model = $controller->ControllerAction->model();
				$action = $model->action;
				
				// TODO-jeff: need to check for roles belonging to institutions
				if (array_key_exists($model->alias, $controller->ControllerAction->models)) {
					$authorized = $AccessControl->check([$controller->name, $model->alias, $action]);
				} else {
					$authorized = $AccessControl->check([$controller->name, $action]);
				}
			} else { // normal actions from Controller
				$authorized = $AccessControl->check([$controller->name, $action]);
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
