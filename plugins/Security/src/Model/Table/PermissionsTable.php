<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;

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
				$flag = 1;
			}
		}
		return $flag;
	}

	private function showOperation(Event $event, $entity, $operation) {
		// $mode = $this->request->query('mode');
		// $html = '';
		// $flag = $this->check($entity, $operation);
		// $alias = $this->alias();

		// if ($mode == 'edit') {
		// 	$id = $entity->id;
		// 	$roleId = $this->request->pass[1];
		// 	$Form = $event->subject()->Form;
		// 	$options = ['type' => 'checkbox', 'class' => 'icheck-input', 'label' => false];
		// 	if ($flag == 1) {
		// 		$options['checked'] = 'checked';
		// 	} else if ($flag == -1) {
		// 		$options['disabled'] = 'disabled';
		// 	$html .= $Form->hidden("$alias.$id.$operation", ['value' => 0]);
		// 	}
			
		// 	$permissionId = isset($entity->Permissions['id']) ? $entity->Permissions['id'] : 0;
		// 	$html .= $Form->input("$alias.$id.$operation", $options);
		// 	$html .= $Form->hidden("$alias.$id.id", ['value' => $permissionId]);
		// 	$html .= $Form->hidden("$alias.$id.security_function_id", ['value' => $id]);
		// 	$html .= $Form->hidden("$alias.$id.security_role_id", ['value' => $roleId]);
		// 	$event->subject()->HtmlField->includes['icheck'] = [
		// 		'include' => true,
		// 		'css' => 'OpenEmis.../plugins/icheck/skins/minimal/blue',
		// 		'js' => [
		// 			'OpenEmis.../plugins/icheck/jquery.icheck.min',
		// 			'OpenEmis.../plugins/tableCheckable/jquery.tableCheckable'
		// 		]
		// 	];
		// } else {
		// 	$icons = [-1 => '<i class="fa fa-minus grey"></i>', 0 => '<i class="fa kd-cross red"></i>', 1 => '<i class="fa kd-check green"></i>'];
		// 	$html = $icons[$flag];
		// }
		// return $html;
	}

	public function onGetFunction(Event $event, Entity $entity) {
		return $entity->name;
	}

	public function onGetView(Event $event, Entity $entity) {
		return $this->showOperation($event, $entity, '_view');
	}

	public function onGetEdit(Event $event, Entity $entity) {
		return $this->showOperation($event, $entity, '_edit');
	}

	public function onGetAdd(Event $event, Entity $entity) {
		return $this->showOperation($event, $entity, '_add');
	}

	public function onGetDelete(Event $event, Entity $entity) {
		return $this->showOperation($event, $entity, '_delete');
	}

	public function onGetExecute(Event $event, Entity $entity) {
		return $this->showOperation($event, $entity, '_execute');
	}

	public function indexEdit() {
		$controller = $this->controller;

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->data($this->alias());
			$entities = $this->newEntities($data);

			foreach ($entities as $entity) {
				$this->save($entity);
			}
			$url = ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => $this->alias()];
			$url = array_merge($url, $this->request->query, $this->request->pass);
			$url[0] = 'index';
			unset($url['mode']);
			
			return $this->controller->redirect($url);
		}
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

		$modules = ['Institutions', 'Students', 'Staff', 'Reports', 'Administration'];
		$this->setupTabElements($modules);

		$module = $this->request->query('module');
		if (empty($module)) {
			$module = current($modules);
			$this->request->query['module'] = $module;
		}
		$controller->set('selectedAction', $module);
		$controller->set('operations', $this->operations);
	}

	public function afterAction(Event $event, ArrayObject $config) {
		if ($this->request->query('mode') == 'edit') {
			$config['formButtons'] = true;
			$config['url'] = $config['buttons']['index']['url'];
			$config['url'][0] = 'indexEdit';
			$config['url'][1] = $this->request->pass[1];
		}
	}

	// Event: ControllerAction.Model.index.beforeAction
	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$controller = $this->controller;

		if (count($this->request->pass) != 2) {
			$event->stopPropagation();
			return $this->controller->redirect(['action' => 'Roles']);
		}
		$roleId = $this->request->pass[1];
		$module = $this->request->query('module');
		$settings['pagination'] = false;
		
		// $this->clean($query, $roleId);
		
		$query = $this->getPermissions($roleId, $module);
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

	private function getPermissions($roleId, $module) {
		$query = $this->SecurityFunctions
			->find()
			->find('visible')
			->select([
				'SecurityFunctions.id', 'SecurityFunctions.name', 'SecurityFunctions.controller', 
				'SecurityFunctions.module', 'SecurityFunctions.category', 
				'SecurityFunctions._view', 'SecurityFunctions._add', 'SecurityFunctions._edit',
				'SecurityFunctions._delete', 'SecurityFunctions._execute',
				'Permissions.id', 'Permissions._view', 'Permissions._add', 'Permissions._edit',
				'Permissions._delete', 'Permissions._execute'
			])
			->leftJoin(
				['Permissions' => 'security_role_functions'], 
				['Permissions.security_function_id = SecurityFunctions.id', 'Permissions.security_role_id = ' . $roleId]
			)
			->where(['SecurityFunctions.module' => $module])
			->order([
				'SecurityFunctions.order'
			])
			;
		return $query;
	}

	public function edit($id=0) {
		$request = $this->request;
		$primaryKey = $this->primaryKey();
		$idKey = $this->aliasField($primaryKey);

		if ($this->exists([$idKey => $id])) {
			$module = $this->request->query('module');
			$query = $this->getPermissions($id, $module);

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

		} else {
			$this->Alert->warning('general.notExists');
			$params = $this->ControllerAction->params();
			$action = array_merge(['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'index'], $params);
			return $this->controller->redirect($action);
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

	// clean up old security functions
	// private function clean(Query $query, $roleId) {
	// 	$resultSet = $query
	// 		->contain(['SecurityFunctions'])
	// 		->where([$this->aliasField('security_role_id') => $roleId])
	// 		->all()
	// 	;
		
	// 	foreach ($resultSet as $entity) {
	// 		if (empty($entity->security_function)) {
	// 			$this->delete($entity);
	// 		}
	// 	}
	// }
}
