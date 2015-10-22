<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;
use Cake\ORM\TableRegistry;

class PermissionsTable extends AppTable {
	private $operations = ['_view', '_edit', '_add', '_delete', '_execute'];

	public function initialize(array $config) {
		$this->table('security_role_functions');
		parent::initialize($config);

		$this->belongsTo('SecurityRoles', 		['className' => 'Security.SecurityRoles']);
		$this->belongsTo('SecurityFunctions',	['className' => 'Security.SecurityFunctions']);
	}

	private function check($entity, $operation) {
		$flag = 0;
		if (empty($entity->$operation)) {
			$flag = -1;
		} else if (!empty($entity->Permissions)) {
			if (!is_null($entity->Permissions[$operation])) {
				$flag = $entity->Permissions[$operation];
			}
		}
		return $flag;
	}

	public function beforeAction(Event $event) {
		$controller = $this->controller;

		$this->ControllerAction->field('function');
		$this->ControllerAction->field('security_role_id', ['visible' => false]);
		$this->ControllerAction->field('security_function_id', ['visible' => false]);

		$checkboxOptions = ['tableColumnClass' => 'checkbox-column'];
		$this->ControllerAction->field('_view', $checkboxOptions);
		$this->ControllerAction->field('_edit', $checkboxOptions);
		$this->ControllerAction->field('_add', $checkboxOptions);
		$this->ControllerAction->field('_delete', $checkboxOptions);
		$this->ControllerAction->field('_execute', $checkboxOptions);

		$modules = ['Institutions', 'Students', 'Staff', 'Guardians', 'Reports', 'Administration'];
		$this->setupTabElements($modules);

		$module = $this->request->query('module');
		if (empty($module)) {
			$module = current($modules);
			$this->request->query['module'] = $module;
		}
		$controller->set('selectedAction', $module);
		$controller->set('operations', $this->operations);
	}

	// Event: ControllerAction.Model.index.beforeAction
	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$controller = $this->controller;

		if (count($this->request->pass) != 2) {
			$event->stopPropagation();
			return $this->controller->redirect(['action' => 'Roles']);
		}
		$roleId = $this->request->pass[1];
		if (! $this->checkRolesHierarchy($roleId)) {
			$action = array_merge(['plugin' => 'Security', 'controller' => 'Securities', 'action' => $this->alias(), '0' => 'index']);
			$event->stopPropagation();
			return $this->controller->redirect($action);
		}
		$module = $this->request->query('module');
		$settings['pagination'] = false;
		
		$query = $this->SecurityFunctions->find()->find('permissions', ['roleId' => $roleId, 'module' => $module]);
		return $query;
	}

	public function indexAfterAction(Event $event, $data) {
		$list = [];
		$icons = [
			-1 => '<i class="fa fa-minus grey"></i>', 
			0 => '<i class="fa kd-cross red"></i>', 
			1 => '<i class="fa kd-check green"></i>'
		];

		foreach ($data as $obj) {
			if (!array_key_exists($obj->category, $list)) {
				$list[$obj->category] = [];
			}
			foreach ($this->operations as $op) {
				$flag = $this->check($obj, $op);
				$obj->Permissions[$op] = $icons[$flag];
			}
			$list[$obj->category][] = $obj;
		}
		return $list;
	}

	public function checkRolesHierarchy($roleId) {
		$user = $this->Auth->user();
		$userId = $user['id'];
		if ($user['super_admin'] == 1) { // super admin will show all roles
			$userId = null;
		}
		$GroupRoles = TableRegistry::get('Security.SecurityGroupUsers');
		$userRole = $GroupRoles
			->find()
			->contain('SecurityRoles')
			->order(['SecurityRoles.order'])
			->where([
				$GroupRoles->aliasField('security_user_id') => $userId
			])
			->first();

		$SecurityRolesTable = $this->SecurityRoles;
		$roleOrder = $this->SecurityRoles->get($roleId)->order;

		// Redirect the user out of the user is not allowed to edit the permission
		if ($roleOrder > $userRole['security_role']['order']) {
			return true;
		} else {
			return false;
		}
	}

	public function edit($roleId=0) {
		$request = $this->request;
		$params = $this->ControllerAction->paramsQuery();

		if (! $this->checkRolesHierarchy($roleId)) {
			$action = array_merge(['plugin' => 'Security', 'controller' => 'Securities', 'action' => $this->alias(), '0' => 'index']);
			return $this->controller->redirect($action);
		}
		
		if ($request->is(['post', 'put'])) {
			$permissions = $request->data($this->alias());
			if (!empty($permissions)) {
				foreach ($permissions as $row) {
					$defaultData = ['_view' => 0, '_edit' => 0, '_add' => 0, '_delete' => 0, '_execute' => 0, 'security_role_id' => $roleId];
					$entity = $this->newEntity(array_merge($defaultData, $row));
					$this->save($entity);
				}
			}
			$this->Alert->success('general.edit.success');

			$action = array_merge(['plugin' => 'Security', 'controller' => 'Securities', 'action' => $this->alias(), 'index', $roleId], $params);
			return $this->controller->redirect($action);
		} else {
			$module = $this->request->query('module');
			$query = $this->SecurityFunctions->find()->find('permissions', ['roleId' => $roleId, 'module' => $module]);
			$data = $query->all();

			$list = [];
			foreach ($data as $obj) {
				if (!array_key_exists($obj->category, $list)) {
					$list[$obj->category] = [];
				}
				foreach ($this->operations as $op) {
					$flag = $this->check($obj, $op);
					$obj->Permissions[$op] = $flag;
				}
				$list[$obj->category][] = $obj;
			}
			$this->controller->set('data', $list);
		}
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
    	$id = $this->request->pass[1];

		if ($action == 'index') {
			$toolbarButtons['back'] = $buttons['back'];
			$toolbarButtons['back']['type'] = 'button';
			$toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
			$toolbarButtons['back']['attr'] = $attr;
			$toolbarButtons['back']['attr']['title'] = __('Back');
			$toolbarButtons['back']['url']['action'] = 'Roles';

			$toolbarButtons['edit'] = $buttons['index'];
			$toolbarButtons['edit']['url'][0] = 'edit';
			$toolbarButtons['edit']['url'][] = $id;
			$toolbarButtons['edit']['type'] = 'button';
			$toolbarButtons['edit']['label'] = '<i class="fa kd-edit"></i>';
			$toolbarButtons['edit']['attr'] = $attr;
			$toolbarButtons['edit']['attr']['title'] = __('Edit');
		} else if ($action == 'edit') {
			$toolbarButtons['back']['url']['action'] = 'Permissions';
			$toolbarButtons['back']['url'][0] = 'index';
			unset($toolbarButtons['list']);
		}
    }

	private function setupTabElements($modules) {
		$controller = $this->controller;
		$tabElements = [];
		$url = ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => $this->alias()];
		if (!empty($this->request->pass)) {
			$url = array_merge($url, $this->request->pass);
		}
		if (!empty($this->request->query)) {
			$url = array_merge($url, $this->request->query);
		}
		
		foreach ($modules as $module) {
			$tabElements[$module] = [
				'url' => array_merge($url, ['module' => $module]),
				'text' => __($module)
			];
		}
		$controller->set('tabElements', $tabElements);
	}
}
