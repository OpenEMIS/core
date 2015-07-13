<?php
namespace App\Controller\Component;

use Cake\I18n\Time;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

class AccessControlComponent extends Component {
	private $controller;
	private $action;
	private $Session;

	protected $_defaultConfig = [
		'operations' => ['_view', '_add', '_edit', '_delete', '_execute'],
		'ignoreList' => [],
		'separator' => '|'
	];

	public $components = ['Auth', 'ControllerAction'];

	public function initialize(array $config) {
		$this->controller = $this->_registry->getController();
		$this->action = $this->request->params['action'];
		$this->Session = $this->request->session();

		// $this->Session->delete('Permissions');
		if (!is_null($this->Auth->user()) && $this->Auth->user('super_admin') == 0) {
			if (!$this->Session->check('Permissions')) {
				$this->buildPermissions();
			} else {
				// check if permission is updated and rebuild
				$userId = $this->Auth->user('id');
				$SecurityRoleFunctions = TableRegistry::get('Security.SecurityRoleFunctions');
				$SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');

				$roles = $SecurityGroupUsers
					->find('list', ['keyField' => 'security_role_id', 'valueField' => 'security_role_id'])
					->where([$SecurityGroupUsers->aliasField('security_user_id') => $userId])
					->toArray();

				$entity = $SecurityRoleFunctions
					->find()
					->where([$SecurityRoleFunctions->aliasField('security_role_id') . ' IN' => $roles])
					->order([$SecurityRoleFunctions->aliasField('modified') => 'DESC'])
					->first();

				if (!is_null($entity)) {
					$lastModified = $this->Session->read('Permissions.lastModified');

					if ($entity->modified->gt($lastModified)) {
						$this->buildPermissions();
					}
				}
			}
		}
	}

	public function startup(Event $event) {
		// pr($this->ControllerAction->buttons);
	}

	public function buildPermissions() {
		$operations = $this->config('operations');
		$separator = $this->config('separator');
		$userId = $this->Auth->user('id');
		$Users = TableRegistry::get('User.Users');
		$SecurityRoleFunctions = TableRegistry::get('Security.SecurityRoleFunctions');
		$userObj = $Users->findById($userId)->contain(['SecurityRoles'])->first();
		$lastModified = null;

		foreach ($userObj->security_roles as $role) { // for each role in user
			$roleId = $role->id;
			$functions = $SecurityRoleFunctions->findAllBySecurityRoleId($roleId)->contain(['SecurityFunctions'])->all();
			
			foreach ($functions as $entity) { // for each function in roles
				if (!empty($entity->security_function)) {
					$function = $entity->security_function;
					if (is_null($lastModified) || (!is_null($lastModified) && $lastModified->lt($entity->modified))) {
						$lastModified = $entity->modified;
					}

					foreach ($operations as $op) { // for each operation in function
						if (!empty($function->$op) && $entity->$op == 1) {
							$actions = explode($separator, $function->$op);

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

		$this->Session->write('Permissions.lastModified', $lastModified);
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
	
	public function check($url=[], $roleId=0) {
		$superAdmin = $this->Auth->user('super_admin');

		if ($superAdmin) {
			return true;
		}

		if (empty($url)) {
			$url = [$this->controller->name, $this->action];
		}

		$url = array_merge(['Permissions'], $url);
		$permissionKey = implode('.', $url);
		// pr($permissionKey);

		if ($this->Session->check($permissionKey)) {
			if ($roleId != 0) {
				$roles = $this->Session->read($permissionKey);
				return in_array($roleId, $roles);
			} else {
				return true;
			}
		}
		return false;
	}

	// determines whether the action is required for access control checking
	public function isIgnored($controller, $action) {
		$ignoreList = $this->config('ignoreList');
		$ignored = false;

		if (array_key_exists($controller, $ignoreList)) {
			if (!empty($ignoreList[$controller])) {
				$actions = $ignoreList[$controller];
				if (in_array($action, $actions)) {
					$ignored = true;
				}
			} else {
				$ignored = true;
			}
		}

		return $ignored;
	}
}
