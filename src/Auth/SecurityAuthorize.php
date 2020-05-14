<?php
namespace App\Auth;

use Cake\Auth\BaseAuthorize;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;

class SecurityAuthorize extends BaseAuthorize
{
    public function authorize($user, Request $request)
    {
        $controller = $this->_registry->getController();
        $action = $request->params['action'];
        $AccessControl = $controller->AccessControl;
        $authorized = false;

        if (!$request->is('ajax') && $request->params['_ext'] != 'json') {
            // Set for roles belonging to the controller
            $roles = [];
            $event = $controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
            if ($event->result) {
                $roles = $event->result;
            }

            $event = $controller->dispatchEvent('Controller.SecurityAuthorize.isActionIgnored', [$action], $this);
            if ($event->result == true) {
                $authorized = true;
            }
            if ($authorized || $user['super_admin'] == true || $user['username'] == 'superrole' || $user['username'] == true) {
                $authorized = true;
            } elseif ($action == 'ComponentAction') { // actions from ControllerActionComponent
                $model = $controller->ControllerAction->model();
                $action = $model->action;
                if (array_key_exists($model->alias, $controller->ControllerAction->models)) {
                    $authorized = $AccessControl->check([$controller->name, $model->alias, $action], $roles);
                } else {
                    $authorized = $AccessControl->check([$controller->name, $action], $roles);
                }
            } else { // normal actions from Controller
                $isCAv4 = ctype_upper(substr($action, 0, 1));
                // CAv4 should use uppercase for action names
                if ($isCAv4) {
                    $pass = $request->pass;
                    $model = $action;
                    $action = isset($pass[0]) ? $pass[0] : 'index';
                    $authorized = $AccessControl->check([$controller->name, $model, $action], $roles);
                } else {
                    $authorized = $AccessControl->check([$controller->name, $action], $roles);
                }
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
