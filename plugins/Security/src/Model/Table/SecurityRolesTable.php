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
		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
					'filter' => 'security_group_id'
				]);
		}
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
		} else if ($selectedAction == 'user') {
			// for all other actions for user group
			$securityGroupId = $this->request->query('security_group_id');
			if ($this->behaviors()->has('Reorder')) {
				$this->behaviors()->get('Reorder')->config([
						'filterValues' => [$securityGroupId]
					]);
			}
		} else {
			if ($this->behaviors()->has('Reorder')) {
				$this->behaviors()->get('Reorder')->config([
						'filterValues' => [-1, 0]
					]);
			}
		}
	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if ($this->behaviors()->has('Reorder')) {
			if (isset($data[$this->alias()]['security_group_id'])) {
				$this->behaviors()->get('Reorder')->config([
					'filterValues' => [$data[$this->alias()]['security_group_id']]
				]);
			}	
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
			
			if ($this->Auth->user('super_admin') != 1) {
				$groupOptions = $this->SecurityGroups->find('list')
					->find('byUser', ['userId' => $this->Auth->user('id')])
					->toArray();
			} else {
				$SecurityGroupsTable = $this->SecurityGroups;
				$InstitutionsTable = TableRegistry::get('Institution.Institutions');
				$institutionSecurityGroup = $InstitutionsTable->find('list');
				$groupOptions = $SecurityGroupsTable->find('list')->where([
						'NOT EXISTS ('.$institutionSecurityGroup->sql().' WHERE '.$InstitutionsTable->aliasField('security_group_id').' = '.$SecurityGroupsTable->aliasField('id').')'
					])
					->toArray();
			}
			

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
		$user = $this->Auth->user();
		$userId = $user['id'];
		if ($user['super_admin'] == 1) { // super admin will show all roles
			$userId = null;
		}
		$count = 0;
		$GroupRoles = TableRegistry::get('Security.SecurityGroupUsers');
		$selectedGroup = $request->query('security_group_id');
		if ($type == 'system') {
			$query
				->where([$this->aliasField('security_group_id') => 0]) // custom system defined roles
				->orWhere([$this->aliasField('security_group_id') => -1]); // fixed system defined roles
			if (!is_null($userId)) {
				$userRole = $GroupRoles
				->find()
				->contain('SecurityRoles')
				->order(['SecurityRoles.order'])
				->where([
					$GroupRoles->aliasField('security_user_id') => $userId,
					'SecurityRoles.security_group_id IN ' => [-1,0]
				])
				->first();
				$query->andWhere([$this->aliasField('order').' > ' => $userRole['security_role']['order']]);
			}		
		} else {
			$query
				->where([$this->aliasField('security_group_id') => $selectedGroup]);
			if (!is_null($userId)) {
				$userRole = $GroupRoles
				->find()
				->contain('SecurityRoles')
				->order(['SecurityRoles.order'])
				->where([
					$GroupRoles->aliasField('security_user_id') => $userId, 
					'SecurityRoles.security_group_id' => $selectedGroup
				])
				->first();
				$query->andWhere([$this->aliasField('order').' > ' => $userRole['security_role']['order']]);
			}
		}
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		
		$attr = ['plugin', 'controller', 'action', 'security_group_id', 0, 1];
		$permissionBtn = ['permissions' => $buttons['view']];
		$permissionBtn['permissions']['url']['action'] = 'Permissions';
		$permissionBtn['permissions']['url'][0] = 'index';
		$permissionBtn['permissions']['label'] = '<i class="fa fa-key"></i>' . __('Permissions');

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
	public function getPrivilegedRoleOptionsByGroup($groupId=null, $userId=null, $createUserGroup=false) {
		$roleOptions = [];

		// -1 is system defined roles (not editable)
		// 0 is system defined roles (editable)
		// >1 is user defined roles in specific group
		$groupIds = [-1, 0];

		if (!is_null($userId)) { // userId will be null if he/she is a super admin

			$userRoleOptions = [];
			$systemRoleOptions = [];

			$GroupRoles = TableRegistry::get('Security.SecurityGroupUsers');

			if (!$createUserGroup) {
				// Get the highest system role
				$highestSystemRole = $this->find()
				->matching('GroupUsers')
				->where([
					'SecurityGroupUsers.security_user_id' => $userId,
					$this->aliasField('security_group_id') . ' IN ' => $groupIds,
					'SecurityGroupUsers.security_group_id' => $groupId
				])
				->order([$this->aliasField('order')])
				->first();
			} else {
				// If the user is a restricted user and is creating a user group
				$highestSystemRole = $this->find()->where([$this->aliasField('name') => 'Group Administrator'])->first();
			}
			

			// If the user has a system role, then populate the system role options
			// find the list of roles with lower privilege than the current highest privilege role assigned to this user
			if (!empty($highestSystemRole)) {
				$systemRoleOptions = $this
					->find('list')
					->find('visible')
					->where([
						$this->aliasField('security_group_id'). ' IN ' => $groupIds,
						$this->aliasField('order') . ' > ' => $highestSystemRole['order'], // the greater the order value, the lower privilege the role is
					])
					->order([$this->aliasField('order')])
					->toArray();

				// If the user has system role and has access to add role, then the user will be able to see the user group roles also,
				// however, if the users they have their own user group role, then they can only see their own user group role below their own, this is overwritten in the code below
				$userRoleOptions = $this
					->find('list')
					->find('visible')
					->where([
						$this->aliasField('security_group_id') => $groupId,
					])
					->order([$this->aliasField('order')])
					->toArray();
			}

			// this will show only roles of the user in the specified group ($groupId)
			$highestUserRole = $GroupRoles
				->find()
				->contain(['SecurityRoles'=> function ($q) {
							return $q->where(['SecurityRoles.security_group_id NOT IN ' => [-1, 0]]);
						}])
				->order(['SecurityRoles.order']) // if user is assigned more than one role, therefore the ordering is necessary
				->where([
					$GroupRoles->aliasField('security_group_id') => $groupId,
					$GroupRoles->aliasField('security_user_id') => $userId
				])
				->first();

			// If the user has a user role, then populate the user role options
			// find the list of roles with lower privilege than the current highest privilege role assigned to this user
			if (!empty($highestUserRole['security_role'])) {
				$userRoleOptions = $this
					->find('list')
					->find('visible')
					->where([
						$this->aliasField('security_group_id') => $groupId,
						$this->aliasField('order') . ' > ' => $highestUserRole['security_role']['order'],
					])
					->order([$this->aliasField('order')])
					->toArray();
			}
			// Merge the permission of the user's system role and user role
			$roleOptions = $systemRoleOptions + $userRoleOptions;

		} else { // super admin will show all roles of system and group specific

			// adding the user role group in
			if (!is_null($groupId)) {
				array_push($groupIds, $groupId);
			}

			$roleOptions = $this
				->find('list')
				->find('visible')
				->where([$this->aliasField('security_group_id') . ' IN ' => $groupIds])
				->order([$this->aliasField('security_group_id'), $this->aliasField('order')])
				->toArray();
		}
		return $roleOptions;
	}

	public function onGetName(Event $event, Entity $entity) {
		//Transalation is only for security roles
		return ($entity->security_group_id == -1) ? __($entity->name) : $entity->name;
	}

	public function getGroupAdministratorEntity() {
		return $this->find()
			->where([
				$this->aliasField('name') => 'Group Administrator'
			])
			->first();
	}
}
