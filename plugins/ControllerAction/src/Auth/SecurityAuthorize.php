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
				$authorized = $controller->AccessControl->check($controller->name, [$model->alias, $action]);
			} else { // super admin have access to every functions
				$authorized = true;
			}
		}
		if (!$authorized) {
			$controller->Alert->error('security.noAccess');
		}
		return $authorized;
	}
}
