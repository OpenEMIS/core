<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

class AccessControlComponent extends Component {
	public $controller;
	public $action;
	public $Session;
	public $operations = ['_view', '_add', '_edit', '_delete', '_execute'];
	public $separator = '|';

	public $components = ['Auth'];

	public function initialize(array $config) {
		$this->controller = $this->_registry->getController();
		$this->action = $this->request->params['action'];
		$this->Session = $this->request->session();

		if (!is_null($this->Auth->user()) && !$this->Session->check('Permissions')) {
			$this->buildPermissions();
		} else {
			// check last updated and rebuild permissions;
		}
		// pr($this->Session->read('Permissions'));
		// $this->Session->delete('Permissions');
	}

	public function buildPermissions() {
		$userId = $this->Auth->user('id');
		$Users = TableRegistry::get('User.Users');
		$SecurityRoleFunctions = TableRegistry::get('Security.SecurityRoleFunctions');
		$userObj = $Users->findById($userId)->contain(['SecurityRoles'])->first();

		foreach ($userObj->security_roles as $role) { // for each role in user
			$roleId = $role->id;
			$functions = $SecurityRoleFunctions->findAllBySecurityRoleId($roleId)->contain(['SecurityFunctions'])->all();
			
			foreach ($functions as $entity) { // for each function in roles
				if (!empty($entity->security_function)) {
					$function = $entity->security_function;

					foreach ($this->operations as $op) { // for each operation in function
						if (!empty($function->$op)) {
							$actions = explode($this->separator, $function->$op);

							if (is_array($actions)) {
								foreach ($actions as $action) { // for each action in operation
									if (!empty($action)) {
										$permission = implode('.', [$function->controller, $action]);
										$this->addPermission($permission, $roleId);
									}
								}
							} else {
								$permission = implode('.', [$function->controller, $action]);
								$this->addPermission($permission, $roleId);
							}
						}
					}
				}
			}
		}
	}

	public function addPermission($permission, $roleId) {
		$permissionKey = 'Permissions.' . $permission;
		if (!$this->Session->check($permissionKey)) {
			$this->Session->write($permissionKey, [$roleId]);
		} else {
			$roles = $this->Session->read($permissionKey);
			if (!in_array($roleId, $roles)) {
				$roles[] = $roleId;
			}
			$this->Session->write($permissionKey, $roles);
		}
	}

	public function check($roleId=0) {
		$permissionKey = implode('.', ['Permissions', $this->controller->name, $this->action]);

		if ($this->Session->check($permissionKey)) {
			if ($roleId != 0) {
				$roles = $this->Session->read($permissionKey);
				if (in_array($roleId, $roles)) {
					return true;
				} else {
					return false;
				}
			} else {
				return true;
			}
		}
		return false;
	}
}
