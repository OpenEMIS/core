<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;

class SecurityRolesTable extends AppTable {
	use MessagesTrait;

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('SecurityGroups', ['className' => 'Security.UserGroups']);

		$this->belongsToMany('SecurityFunctions', [
			'className' => 'Security.SecurityFunctions',
			'through' => 'Security.SecurityRoleFunctions'
		]);

		$this->belongsToMany('GroupUsers', [
			'className' => 'Security.UserGroups',
			'joinTable' => 'security_group_users',
			'foreignKey' => 'security_role_id',
			'targetForeignKey' => 'security_group_id',
			'through' => 'Security.SecurityGroupUsers',
			'dependent' => true
		]);

		$this->addBehavior('Reorder');
	}

	public function beforeAction(Event $event) {
		$controller = $this->controller;
		$types = ['user', 'system'];

		$tabElements = [
			'user' => [
				'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => 'Roles', 'type' => 'user'],
				'text' => $this->getMessage($this->aliasField('userRoles'))
			],
			'system' => [
				'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => 'Roles', 'type' => 'system'],
				'text' => $this->getMessage($this->aliasField('systemRoles'))
			]
		];

		// check for roles privileges
		if (!$this->AccessControl->check(['Securities', 'UserRoles', 'view'])) {
			unset($tabElements['user']);
			unset($types[0]);
		} else if (!$this->AccessControl->check(['Securities', 'SystemRoles', 'view'])) {
			unset($tabElements['system']);
			unset($types[1]);
		}

		$selectedAction = $this->request->query('type');
		if (empty($selectedAction) || !in_array($selectedAction, $types)) {
			$selectedAction = current($types);
		}

		$this->request->query['type'] = $selectedAction;
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $selectedAction);

		$this->ControllerAction->field('security_group_id', ['viewType' => $selectedAction]);

		$action = $this->ControllerAction->action();
		if ($action == 'index' && $selectedAction == 'user') {
			$toolbarElements = [
				['name' => 'Security.Roles/controls', 'data' => [], 'options' => []]
			];
			$this->controller->set('toolbarElements', $toolbarElements);
		}
	}

	public function onInitializeButtons(Event $event, ArrayObject $buttons, $action, $isFromModel) {
		// to handle buttons visibility on a different set of permissions
		$selectedAction = $this->request->query('type');
		if (!empty($selectedAction)) {
			$actions = ['user' => 'UserRoles', 'system' => 'SystemRoles'];
			
			$permissions = ['add', 'edit', 'remove'];
			foreach ($permissions as $permission) {
				if (!$this->AccessControl->check(['Securities', $actions[$selectedAction], $permission])) {
					unset($buttons[$permission]);
				}
			}
		}
		parent::onInitializeButtons($event, $buttons, $action, $isFromModel);
	}

	public function onUpdateFieldSecurityGroupId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'index') {
			$attr['visible'] = false;
		}

		$viewType = $attr['viewType'];
		if ($viewType == 'user') {
			// TODO-jeff: need to restrict to roles that have access to their groups
			$groupOptions = $this->SecurityGroups->find('list')
				->find('byUser', ['userId' => $this->Auth->user('id')])
				->toArray();

			$selectedGroup = $this->queryString('security_group_id', $groupOptions);
			$this->advancedSelectOptions($groupOptions, $selectedGroup);
			$request->query['security_group_id'] = $selectedGroup;

			$this->controller->set('groupOptions', $groupOptions);
			$attr['options'] = $groupOptions;
		} else {
			$attr['type'] = 'hidden';
			$attr['value'] = 0;
		}

		return $attr;
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$type = $request->query('type');
		
		$selectedGroup = $request->query('security_group_id');
		if ($type == 'system') {

			$query
			->where([$this->aliasField('security_group_id') => 0])		// custom system defined roles
			->orWhere([$this->aliasField('security_group_id') => -1]);	// fixed system defined roles
		} else {
			$query->where([$this->aliasField('security_group_id') => $selectedGroup]);
		}
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		
		$attr = ['plugin', 'controller', 'action', 'security_group_id', 0, 1];
		$permissionBtn = ['permissions' => $buttons['view']];
		$permissionBtn['permissions']['url']['action'] = 'Permissions';
		$permissionBtn['permissions']['url'][0] = 'index';
		$permissionBtn['permissions']['label'] = __('Permissions');

		// foreach ($permissionBtn['permissions']['url'] as $key => $val) {
		// 	if (!in_array($key, $attr)) {
		// 		unset($permissionBtn['permissions']['url'][$key]);
		// 	}
		// }

		$buttons = array_merge($permissionBtn, $buttons);

		$groupId = $entity->security_group_id;
		// -1 = system roles, we are not allowing users to modify system roles
		// removing all buttons from the menu
		if ($groupId == -1) {
			if (array_key_exists('view', $buttons)) {
				unset($buttons['view']);
			}
			if (array_key_exists('edit', $buttons)) {
				unset($buttons['edit']);
			}
			if (array_key_exists('remove', $buttons)) {
				unset($buttons['remove']);
			}
		}
		return $buttons;
	}

	public function findByInstitution(Query $query, $options) {
		$ids = [-1, 0];
		if (array_key_exists('id', $options)) {
			// need to get the security_group_id of the institution
			$Institution = TableRegistry::get('Institution.Institutions');
			$institutionQuery = $Institution->find()
				->where([$Institution->aliasField($Institution->primaryKey()) => $options['id']])
				->first()
				;
			if ($institutionQuery) {
				if (isset($institutionQuery->security_group_id)) {
					$ids[] = $institutionQuery->security_group_id;
				}
			}
		} 

		return $query->where([$this->aliasField('security_group_id').' IN' => $ids]);
	}

	// this function will return all roles (system roles & user roles) that has lower
	// privileges than the current role of the user in a specific group
	public function getPrivilegedRoleOptionsByGroup($groupId, $userId=null) {
		$roleOptions = [];

		// -1 is system defined roles (not editable)
		// 0 is system defined roles (editable)
		// >1 is user defined roles in specific group
		$groupIds = [-1, 0, $groupId];

		if (!is_null($userId)) { // userId will be null if he/she is a super admin
			$GroupRoles = TableRegistry::get('Security.SecurityGroupUsers');
			// this will show only roles of the user in the specified group ($groupId)
			$query = $GroupRoles
				->find()
				->contain('SecurityRoles')
				->order(['SecurityRoles.order'])
				->where([
					$GroupRoles->aliasField('security_group_id') => $groupId,
					$GroupRoles->aliasField('security_user_id') => $userId
				])
			;

			// get the highest role of the current user
			$highestRole = $query->first();

			if (!is_null($highestRole)) {
				// find the list of roles with lower privilege than the current highest privilege role assigned to this user
				// within -1 (system defined roles), 0 (system defined roles), and user defined roles in the specified group
				$roleOptions = $this
					->find('list')
					->find('visible')
					->where([
						$this->aliasField('security_group_id') . ' IN ' => $groupIds,
						$this->aliasField('order') . ' > ' => $highestRole->security_role->order,
					])
					->order([$this->aliasField('security_group_id'), $this->aliasField('order')])
					->toArray()
				;
			}
		} else { // super admin will show all roles of system and group specific
			$roleOptions = $this
				->find('list')
				->find('visible')
				->where([$this->aliasField('security_group_id') . ' IN ' => $groupIds])
				->order([$this->aliasField('security_group_id'), $this->aliasField('order')])
				->toArray()
			;
		}
		return $roleOptions;
	}

	public function onGetName(Event $event, Entity $entity) {
		//Transalation is only for security roles
		return ($entity->security_group_id == -1) ? __($entity->name) : $entity->name;
	}
}
