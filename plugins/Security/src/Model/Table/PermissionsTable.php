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
	public function initialize(array $config) {
		$this->table('security_role_functions');
		parent::initialize($config);

		$this->belongsTo('SecurityRoles', 		['className' => 'Security.SecurityRoles']);
		$this->belongsTo('SecurityFunctions',	['className' => 'Security.SecurityFunctions']);
	}

	private function check($entity, $operation) {
		$html = '<i class="fa fa-close"></i>';
		if (!empty($entity->Permissions)) {
			if ($entity->Permissions[$operation]) {
				$html = '<i class="fa fa-check"></i>';
			}
		}
		return $html;
	}

	public function onGetFunction(Event $event, Entity $entity) {
		return $entity->name;
	}

	public function onGetView(Event $event, Entity $entity) {
		return $this->check($entity, '_view');
	}

	public function onGetEdit(Event $event, Entity $entity) {
		return $this->check($entity, '_edit');
	}

	public function onGetAdd(Event $event, Entity $entity) {
		return $this->check($entity, '_add');
	}

	public function onGetDelete(Event $event, Entity $entity) {
		return $this->check($entity, '_delete');
	}

	public function onGetExecute(Event $event, Entity $entity) {
		return $this->check($entity, '_execute');
	}

	public function beforeAction(Event $event) {
		$controller = $this->controller;

		$this->ControllerAction->field('function');
		$this->ControllerAction->field('security_role_id', ['visible' => false]);
		$this->ControllerAction->field('security_function_id', ['visible' => false]);
	}

	// Event: ControllerAction.Model.index.beforeAction
	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$controller = $this->controller;

		if (count($this->request->pass) != 2) {
			$event->stopPropagation();
			return $this->controller->redirect(['action' => 'Roles']);
		}
		$roleId = $this->request->pass[1];
		$settings['pagination'] = false;
		
		$this->clean($query, $roleId);

		$modules = ['Institutions', 'Students', 'Staff', 'Reports', 'Administration'];
		$this->setupTabElements($modules);

		$module = $this->request->query('module');
		if (empty($module)) {
			$module = current($modules);
		}
		$controller->set('selectedAction', $module);

		$categoryOptions = $this->setupToolbarElements($module);
		$selectedCategory = $this->request->query('category');

		if (empty($selectedCategory)) {
			$selectedCategory = current($categoryOptions);
		}
		$controller->set('selectedCategory', $selectedCategory);

		$query = $this->SecurityFunctions
			->find()
			->select([
				'SecurityFunctions.id', 'SecurityFunctions.name', 'SecurityFunctions.controller', 
				'SecurityFunctions.module', 'SecurityFunctions.category', 
				'Permissions.id', 'Permissions._view', 'Permissions._add', 'Permissions._edit',
				'Permissions._delete', 'Permissions._execute'
			])
			->join([
				[
					'table' => 'security_role_functions', 'alias' => 'Permissions', 'type' => 'LEFT',
					'conditions' => [
						'Permissions.security_function_id = SecurityFunctions.id',
						'Permissions.security_role_id = ' . $roleId
					]
				]
			])
			->where(['SecurityFunctions.module' => $module, 'SecurityFunctions.category' => $selectedCategory])
			->order([
				'SecurityFunctions.order'
			])
			;

		return $query;
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		$toolbarButtons['back'] = $buttons['back'];
		$toolbarButtons['back']['url']['action'] = 'Roles';
		$toolbarButtons['back']['type'] = 'button';
		$toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
		$toolbarButtons['back']['attr'] = $attr;
		$toolbarButtons['back']['attr']['title'] = __('Back');
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

	private function setupToolbarElements($module) {
		$categoryOptions = $this->SecurityFunctions
			->find('list', ['keyField' => 'category', 'valueField' => 'category'])
			->distinct(['category'])
			->where(['module' => $module])
			->order(['SecurityFunctions.order'])
			->toArray()
		;

		$toolbarElements = [
			['name' => 'Security.Permissions/categories', 'data' => [], 'options' => []]
		];
		$this->controller->set('toolbarElements', $toolbarElements);
		$this->controller->set('categoryOptions', $categoryOptions);
		return $categoryOptions;
	}

	// clean up old security functions
	private function clean(Query $query, $roleId) {
		$resultSet = $query
			->contain(['SecurityFunctions'])
			->where([$this->aliasField('security_role_id') => $roleId])
			->all()
		;
		
		foreach ($resultSet as $entity) {
			if (empty($entity->security_function)) {
				$this->delete($entity);
			}
		}
	}
}
