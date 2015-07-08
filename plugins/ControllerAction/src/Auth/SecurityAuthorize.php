<?php
namespace ControllerAction\Auth;

use Cake\Auth\BaseAuthorize;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;

class SecurityAuthorize extends BaseAuthorize {
	public function authorize($user, Request $request) {
		$controller = $this->_registry->getController();
		$action = $request->params['action'];
		$authorized = false;

		if ($action == 'ComponentAction') {
			$model = $controller->ControllerAction->model();
			$action = $model->action;
			
			if ($user['super_admin'] == 0) {
				// TODO-jeff: need to check for roles belonging to institutions
				if (array_key_exists($model->alias, $controller->ControllerAction->models)) {
					$authorized = $controller->AccessControl->check($controller->name, [$model->alias, $action]);
				} else {
					$authorized = $controller->AccessControl->check($controller->name, $action);
				}
			} else { // super admin have access to every functions
				$authorized = true;
			}
		} else if ($user['super_admin'] == 0) { // not super admin
			// if (isset($controller->publicActions) && in_array($action, $controller->publicActions)) {
				// $authorized = true;
			// }
		} else { // super admin
			$authorized = true;
		}
		if (!$authorized) {
			$controller->Alert->error('security.noAccess');
		}
		return $authorized;
	}
}
