<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Datasource\Exception\RecordNotFoundException;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;
use App\Model\Traits\HtmlTrait;

class SystemGroupsTable extends AppTable {
	use MessagesTrait;
	use HtmlTrait;

	public function initialize(array $config) {
		$this->table('security_groups');
		parent::initialize($config);

		$this->hasMany('Roles', ['className' => 'Security.SecurityRoles', 'dependent' => true]);
		$this->hasOne('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'security_group_id']);
		$this->belongsToMany('Users', [
			'className' => 'Security.Users',
			'joinTable' => 'security_group_users',
			'foreignKey' => 'security_group_id',
			'targetForeignKey' => 'security_user_id',
			'through' => 'Security.SecurityGroupUsers',
			'dependent' => true
		]);
	}

	public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {
		if ($action == 'edit') {
			$includes['autocomplete'] = [
				'include' => true, 
				'css' => ['OpenEmis.../plugins/autocomplete/css/autocomplete'],
				'js' => ['OpenEmis.../plugins/autocomplete/js/autocomplete']
			];
		}
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons',
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->request->data[$this->alias()]['security_group_id'] = $entity->id;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'view') {
			if (!$this->AccessControl->isAdmin()) {
				$userId = $this->Auth->user('id');
				$securityGroupId = $this->request->data[$this->alias()]['security_group_id'];
				$SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
				if (!$SecurityGroupUsersTable->checkEditGroup($userId, $securityGroupId, '_edit')) {
					if (array_key_exists('edit', $toolbarButtons)) {
						unset($toolbarButtons['edit']);
					}
				}
			}
		}
	}

	public function editAfterAction(Event $event, Entity $entity) {
		if (!$this->AccessControl->isAdmin()) {
			$userId = $this->Auth->user('id');
			$securityGroupId = $entity->id;
			$SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
			if (!$SecurityGroupUsersTable->checkEditGroup($userId, $securityGroupId, '_edit')) {
				$urlParams = $this->ControllerAction->url('index');
				$event->stopPropagation();
				return $this->controller->redirect($urlParams);
			}
		}
		$this->request->data['user_search'] = '';
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		$userId = $this->Auth->user('id');
		$securityGroupId = $entity->id;
		// -1 = system roles, we are not allowing users to modify system roles
		// removing all buttons from the menu
		if (!$this->AccessControl->isAdmin()) {
			$SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
			if (!$SecurityGroupUsersTable->checkEditGroup($userId, $securityGroupId, '_edit')) {
				if (array_key_exists('edit', $buttons)) {
					unset($buttons['edit']);
				}
			}

			if (!$SecurityGroupUsersTable->checkEditGroup($userId, $securityGroupId, '_delete')) {
				if (array_key_exists('remove', $buttons)) {
					unset($buttons['remove']);
				}
			}
		}
		
		return $buttons;
	}

	public function beforeAction(Event $event) {
		$controller = $this->controller;
		$tabElements = [
			'UserGroups' => [
				'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => 'UserGroups'],
				'text' => $this->getMessage('UserGroups.tabTitle')
			],
			$this->alias() => [
				'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => $this->alias()],
				'text' => $this->getMessage($this->aliasField('tabTitle'))
			]
		];
		
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());

		$roleOptions = $this->Roles->find('list')->toArray();
		$this->ControllerAction->field('users', [
			'type' => 'user_table', 
			'valueClass' => 'table-full-width',
			'roleOptions' => $roleOptions,
			'visible' => ['index' => false, 'view' => true, 'edit' => true]
		]);

		$this->ControllerAction->setFieldOrder(['name', 'users']);
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->field('no_of_users', ['visible' => ['index' => true]]);
		$this->ControllerAction->setFieldOrder(['name', 'no_of_users']);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$queryParams = $request->query;

		$query->find('inInstitutions');

		if (!array_key_exists('sort', $queryParams) && !array_key_exists('direction', $queryParams)) {
			$query->order([$this->aliasField('name') => 'asc']);
		}

		// filter groups by users permission
		if ($this->Auth->user('super_admin') != 1) {
			$userId = $this->Auth->user('id');
			$query->innerJoin(
				['GroupUsers' => 'security_group_users'],
				[
					'GroupUsers.security_group_id = ' . $this->aliasField('id'),
					'GroupUsers.security_user_id = ' . $userId
				]
			);
			$query->group([$this->aliasField('id')]);
		}
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain(['Users']);
	}

	public function editBeforeAction(Event $event) {
		$this->ControllerAction->field('name', ['type' => 'readonly']);
	}

	public function onGetUserTableElement(Event $event, $action, $entity, $attr, $options=[]) {
		$tableHeaders = [__('OpenEMIS ID'), __('Name'), __('Role')];
		$tableCells = [];
		$alias = $this->alias();
		$key = 'users';

		if ($action == 'index') {
			// not showing
		} else if ($action == 'view') {
			$roleOptions = $attr['roleOptions'];
			$associated = $entity->extractOriginal([$key]);
			if (!empty($associated[$key])) {
				foreach ($associated[$key] as $i => $obj) {
					$rowData = [];
					$rowData[] = $obj->openemis_no;
					$rowData[] = $obj->name;
					$roleId = $obj->_joinData->security_role_id;

					if (array_key_exists($roleId, $roleOptions)) {
						$rowData[] = $roleOptions[$roleId];
					} else {
						$this->log(__METHOD__ . ': Orphan record found for role id: ' . $roleId, 'debug');
						$rowData[] = '';
					}
					$tableCells[] = $rowData;
				}
			}
		} else if ($action == 'edit') {
			$tableHeaders[] = ''; // for delete column
			$Form = $event->subject()->Form;

			$user = $this->Auth->user();
			$userId = $user['id'];
			if ($user['super_admin'] == 1) { // super admin will show all roles
				$userId = null;
			}
			$roleOptions = $this->Roles->getPrivilegedRoleOptionsByGroup($entity->id, $userId);
			if ($this->request->is(['get'])) {
				if (!array_key_exists($alias, $this->request->data)) {
					$this->request->data[$alias] = [$key => []];
				} else {
					$this->request->data[$alias][$key] = [];
				}

				$associated = $entity->extractOriginal([$key]);
				if (!empty($associated[$key])) {
					foreach ($associated[$key] as $i => $obj) {
						$this->request->data[$alias][$key][] = [
							'id' => $obj->id,
							'_joinData' => ['openemis_no' => $obj->openemis_no, 'security_user_id' => $obj->id, 'name' => $obj->name]
						];
					}
				}
			}
			// refer to addEditOnAddUser for http post
			if ($this->request->data("$alias.$key")) {
				$associated = $this->request->data("$alias.$key");

				foreach ($associated as $i => $obj) {
					$joinData = $obj['_joinData'];
					$rowData = [];
					$name = $joinData['name'];
					$name .= $Form->hidden("$alias.$key.$i.id", ['value' => $joinData['security_user_id']]);
					$name .= $Form->hidden("$alias.$key.$i._joinData.openemis_no", ['value' => $joinData['openemis_no']]);
					$name .= $Form->hidden("$alias.$key.$i._joinData.name", ['value' => $joinData['name']]);
					$name .= $Form->hidden("$alias.$key.$i._joinData.security_user_id", ['value' => $joinData['security_user_id']]);
					$rowData[] = $joinData['openemis_no'];
					$rowData[] = $name;
					$rowData[] = $Form->input("$alias.$key.$i._joinData.security_role_id", ['label' => false, 'options' => $roleOptions]);
					$rowData[] = $this->getDeleteButton();
					$tableCells[] = $rowData;
				}
			}
		}
		$attr['tableHeaders'] = $tableHeaders;
    	$attr['tableCells'] = $tableCells;

		return $event->subject()->renderElement('Security.Groups/' . $key, ['attr' => $attr]);
	}

	public function addEditOnAddUser(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$alias = $this->alias();

		if ($data->offsetExists('user_id')) {
			$id = $data['user_id'];
			try {
				$obj = $this->Users->get($id);

				if (!array_key_exists('users', $data[$alias])) {
					$data[$alias]['users'] = [];
				}
				$data[$alias]['users'][] = [
					'id' => $obj->id,
					'_joinData' => ['openemis_no' => $obj->openemis_no, 'security_user_id' => $obj->id, 'name' => $obj->name]
				];
			} catch (RecordNotFoundException $ex) {
				$this->log(__METHOD__ . ': Record not found for id: ' . $id, 'debug');
			}
		}
	}

	public function findInInstitutions(Query $query, array $options) {
		$query->innerJoin(['Institutions' => 'institutions'], ['Institutions.security_group_id = SystemGroups.id']);
		return $query;
	}

	public function onGetNoOfUsers(Event $event, Entity $entity) {
		$id = $entity->id;

		$GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
		$count = $GroupUsers->findAllBySecurityGroupId($id)->count();

		return $count;
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// in case user has been added with the same role twice, we need to filter it
		$this->filterDuplicateUserRoles($data);

		// by default association will be saved automatically, we are turning it off so it will save successfully
		$options['associated'] = false;
	}

	// same logic also in UserGroups, may consider moving it into a behavior
	public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// users can't save properly using associated method
		// until we find a better solution, saving of users for groups will be done in afterSave as of now
		$id = $entity->id;
		$GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
		$GroupUsers->deleteAll(['security_group_id' => $id]);

		if ($entity->has('users')) {
			$users = $entity->users;
			if (!empty($users)) {
				foreach ($users as $user) {
					$query = $GroupUsers->find()->where([
						$GroupUsers->aliasField('security_user_id') => $user['_joinData']['security_user_id'],
						$GroupUsers->aliasField('security_role_id') => $user['_joinData']['security_role_id'],
						$GroupUsers->aliasField('security_group_id') => $id
					]);

					if ($query->count() == 0) {
						$newEntity = $GroupUsers->newEntity([
							'security_user_id' => $user['_joinData']['security_user_id'],
							'security_role_id' => $user['_joinData']['security_role_id'],
							'security_group_id' => $id
						]);

						$GroupUsers->save($newEntity);
					}
				}
			}
		}
	}

	// also exists in UserGroups
	private function filterDuplicateUserRoles(ArrayObject $data) {
		if (array_key_exists('users', $data[$this->alias()])) {
			$roles = [];

			$users = $data[$this->alias()]['users'];
			foreach ($users as $i => $user) {
				$joinData = $user['_joinData'];
				$userRole = $joinData['security_user_id'] . ' - ' . $joinData['security_role_id'];
				if (in_array($userRole, $roles)) {
					unset($data[$this->alias()]['users'][$i]);
				} else {
					$roles[] = $userRole;
				}
			}
		} else {
			$data[$this->alias()]['users'] = [];
		}
	}

	public function ajaxUserAutocomplete() {
		$this->controller->autoRender = false;
		$this->ControllerAction->autoRender = false;

		if ($this->request->is(['ajax'])) {
			$term = $this->request->query['term'];
			$data = $this->Users->autocomplete($term);
			echo json_encode($data);
			die;
		}
	}
}
