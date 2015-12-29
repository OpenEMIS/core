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

class UserGroupsTable extends AppTable {
	use MessagesTrait;
	use HtmlTrait;

	public function initialize(array $config) {
		$this->table('security_groups');
		parent::initialize($config);

		$this->hasMany('Roles', ['className' => 'Security.SecurityRoles', 'dependent' => true]);

		$this->belongsToMany('Users', [
			'className' => 'Security.Users',
			'joinTable' => 'security_group_users',
			'foreignKey' => 'security_group_id',
			'targetForeignKey' => 'security_user_id',
			'through' => 'Security.SecurityGroupUsers',
			'dependent' => true
		]);

		$this->belongsToMany('Areas', [
			'className' => 'Area.Areas',
			'joinTable' => 'security_group_areas',
			'foreignKey' => 'security_group_id',
			'targetForeignKey' => 'area_id',
			'through' => 'Security.SecurityGroupAreas',
			'dependent' => true
		]);

		$this->belongsToMany('Institutions', [
			'className' => 'Institution.Institutions',
			'joinTable' => 'security_group_institutions',
			'foreignKey' => 'security_group_id',
			'targetForeignKey' => 'institution_id',
			'through' => 'Security.SecurityGroupInstitutions',
			'dependent' => true
		]);

		$this->belongsToMany('Roles', [
			'className' => 'Security.SecurityRoles',
			'joinTable' => 'security_group_users',
			'foreignKey' => 'security_group_id',
			'targetForeignKey' => 'security_role_id',
			'through' => 'Security.SecurityGroupUsers',
			'dependent' => true
		]);
	}

	public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {
		if ($action == 'edit') {
			$includes['autocomplete'] = [
				'include' => true, 
				'css' => ['OpenEmis.jquery-ui.min', 'OpenEmis.../plugins/autocomplete/css/autocomplete'],
				'js' => ['OpenEmis.jquery-ui.min', 'OpenEmis.../plugins/autocomplete/js/autocomplete']
			];
		}
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		$userId = $this->Auth->user('id');
		$securityGroupId = $entity->id;
		// -1 = system roles, we are not allowing users to modify system roles
		// removing all buttons from the menu
		if (!$this->AccessControl->isAdmin()) {
			$SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
			if (!$SecurityGroupUsersTable->checkEditGroup($userId, $securityGroupId, '_edit', 'user')) {
				if (array_key_exists('edit', $buttons)) {
					unset($buttons['edit']);
				}
			}

			if (!$SecurityGroupUsersTable->checkEditGroup($userId, $securityGroupId, '_delete', 'user')) {
				if (array_key_exists('delete', $buttons)) {
					unset($buttons['delete']);
				}
			}
		}
		
		return $buttons;
	}

	public function beforeAction(Event $event) {
		$controller = $this->controller;
		$tabElements = [
			$this->alias() => [
				'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => $this->alias()],
				'text' => $this->getMessage($this->aliasField('tabTitle'))
			],
			'SystemGroups' => [
				'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => 'SystemGroups'],
				'text' => $this->getMessage('SystemGroups.tabTitle')
			]
		];

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());

		$this->ControllerAction->field('areas', [
			'type' => 'area_table', 
			'valueClass' => 'table-full-width',
			'visible' => ['index' => false, 'view' => true, 'edit' => true]
		]);
		$this->ControllerAction->field('institutions', [
			'type' => 'institution_table', 
			'valueClass' => 'table-full-width',
			'visible' => ['index' => false, 'view' => true, 'edit' => true]
		]);

		$roleOptions = $this->Roles->find('list')->toArray();
		$this->ControllerAction->field('users', [
			'type' => 'user_table', 
			'valueClass' => 'table-full-width',
			'roleOptions' => $roleOptions,
			'visible' => ['index' => false, 'view' => true, 'edit' => true]
		]);

		$this->ControllerAction->setFieldOrder([
			'name', 'areas', 'institutions', 'users'
		]);
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain(['Areas.Levels', 'Institutions', 'Users', 'Roles']);
	}

	public function onGetAreaTableElement(Event $event, $action, $entity, $attr, $options=[]) {
		$tableHeaders = [__('Level'), __('Code'), __('Area')];
		$tableCells = [];
		$alias = $this->alias();
		$key = 'areas';

		if ($action == 'index') {
			// not showing
		} else if ($action == 'view') {
			$associated = $entity->extractOriginal([$key]);
			if (!empty($associated[$key])) {
				foreach ($associated[$key] as $i => $obj) {
					$rowData = [];
					$rowData[] = [$obj->level->name, ['autocomplete-exclude' => $obj->id]];
					$rowData[] = $obj->code;
					$rowData[] = $obj->name;
					$tableCells[] = $rowData;
				}
			}
		} else if ($action == 'edit') {
			$tableHeaders[] = ''; // for delete column
			$Form = $event->subject()->Form;

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
							'_joinData' => ['level' => $obj->level->name, 'code' => $obj->code, 'area_id' => $obj->id, 'name' => $obj->name]
						];
					}
				}
			}
			// refer to addEditOnAddArea for http post
			if ($this->request->data("$alias.$key")) {
				$associated = $this->request->data("$alias.$key");

				foreach ($associated as $i => $obj) {
					$joinData = $obj['_joinData'];
					$rowData = [];
					$name = $joinData['name'];
					$name .= $Form->hidden("$alias.$key.$i.id", ['value' => $obj['id']]);
					$name .= $Form->hidden("$alias.$key.$i._joinData.level", ['value' => $joinData['level']]);
					$name .= $Form->hidden("$alias.$key.$i._joinData.code", ['value' => $joinData['code']]);
					$name .= $Form->hidden("$alias.$key.$i._joinData.area_id", ['value' => $joinData['area_id']]);
					$name .= $Form->hidden("$alias.$key.$i._joinData.name", ['value' => $joinData['name']]);
					$rowData[] = [$joinData['level'], ['autocomplete-exclude' => $joinData['area_id']]];
					$rowData[] = $joinData['code'];
					$rowData[] = $name;
					$rowData[] = $this->getDeleteButton();
					$tableCells[] = $rowData;
				}
			}
		}
		$attr['tableHeaders'] = $tableHeaders;
    	$attr['tableCells'] = $tableCells;

		return $event->subject()->renderElement('Security.Groups/' . $key, ['attr' => $attr]);
	}

	public function addEditOnAddArea(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$alias = $this->alias();

		if ($data->offsetExists('area_id')) {
			$id = $data['area_id'];
			try {
				$obj = $this->Areas->get($id, ['contain' => 'Levels']);
				
				if (!array_key_exists('areas', $data[$alias])) {
					$data[$alias]['areas'] = [];
				}
				$data[$alias]['areas'][] = [
					'id' => $obj->id,
					'_joinData' => ['level' => $obj->level->name, 'code' => $obj->code, 'area_id' => $obj->id, 'name' => $obj->name]
				];
			} catch (RecordNotFoundException $ex) {
				$this->log(__METHOD__ . ': Record not found for id: ' . $id, 'debug');
			}
		}
	}

	public function onGetInstitutionTableElement(Event $event, $action, $entity, $attr, $options=[]) {
		$tableHeaders = [__('Code'), __('Institution')];
		$tableCells = [];
		$alias = $this->alias();
		$key = 'institutions';

		if ($action == 'index') {
			// not showing
		} else if ($action == 'view') {
			$associated = $entity->extractOriginal([$key]);
			if (!empty($associated[$key])) {
				foreach ($associated[$key] as $i => $obj) {
					$rowData = [];
					$rowData[] = [$obj->code, ['autocomplete-exclude' => $obj->id]];
					$rowData[] = $obj->name;
					$tableCells[] = $rowData;
				}
			}
		} else if ($action == 'edit') {
			$tableHeaders[] = ''; // for delete column
			$Form = $event->subject()->Form;

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
							'_joinData' => ['code' => $obj->code, 'institution_id' => $obj->id, 'name' => $obj->name]
						];
					}
				}
			}
			// refer to addEditOnAddInstitution for http post
			if ($this->request->data("$alias.$key")) {
				$associated = $this->request->data("$alias.$key");

				foreach ($associated as $i => $obj) {
					$joinData = $obj['_joinData'];
					$rowData = [];
					$name = $joinData['name'];
					$name .= $Form->hidden("$alias.$key.$i.id", ['value' => $joinData['institution_id']]);
					$name .= $Form->hidden("$alias.$key.$i._joinData.code", ['value' => $joinData['code']]);
					$name .= $Form->hidden("$alias.$key.$i._joinData.name", ['value' => $joinData['name']]);
					$name .= $Form->hidden("$alias.$key.$i._joinData.institution_id", ['value' => $joinData['institution_id']]);
					$rowData[] = [$joinData['code'], ['autocomplete-exclude' => $joinData['institution_id']]];
					$rowData[] = $name;
					$rowData[] = $this->getDeleteButton();
					$tableCells[] = $rowData;
				}
			}
		}
		$attr['tableHeaders'] = $tableHeaders;
    	$attr['tableCells'] = $tableCells;

		return $event->subject()->renderElement('Security.Groups/' . $key, ['attr' => $attr]);
	}

	public function addEditOnAddInstitution(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$alias = $this->alias();

		if ($data->offsetExists('institution_id')) {
			$id = $data['institution_id'];
			try {
				$obj = $this->Institutions->get($id);

				if (!array_key_exists('institutions', $data[$alias])) {
					$data[$alias]['institutions'] = [];
				}
				$data[$alias]['institutions'][] = [
					'id' => $obj->id,
					'_joinData' => ['code' => $obj->code, 'institution_id' => $obj->id, 'name' => $obj->name]
				];
			} catch (RecordNotFoundException $ex) {
				$this->log(__METHOD__ . ': Record not found for id: ' . $id, 'debug');
			}
		}
	}

	public function onGetUserTableElement(Event $event, $action, $entity, $attr, $options=[]) {
		$tableHeaders = [__('OpenEMIS No'), __('Name'), __('Role')];
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
							'_joinData' => [
								'openemis_no' => $obj->openemis_no, 
								'security_user_id' => $obj->id, 
								'name' => $obj->name,
								'security_role_id' => $obj->_joinData->security_role_id
							]
						];
					}
				} else {
					$groupAdminId = $this->Roles->find()->where([$this->Roles->aliasField('name') => 'Group Administrator'])->first()->id;
					$UserTable = TableRegistry::get('Users');
					$user = $UserTable->get($userId);
					if (empty($this->request->data[$alias][$key])) {
						$this->request->data[$alias][$key][] = [
							'id' => $userId,
							'_joinData' => [
								'openemis_no' => $user->openemis_no, 
								'security_user_id' => $userId, 
								'name' => $user->name,
								'security_role_id' => $groupAdminId
							]
						];
					}
				}
			}
			// refer to addEditOnAddUser for http post
			if ($this->request->data("$alias.$key")) {

				if (!$this->AccessControl->isAdmin()) {
					$roleOptions = $this->Roles->getPrivilegedRoleOptionsByGroup($entity->id, $userId, true);
				}
				// For the original user
				$associated = $entity->extractOriginal([$key]);
				$found = false;
				if (!empty($associated[$key]) && !$entity->isNew()) {
					foreach ($associated[$key] as $i => $obj) {
						if ($obj->id == $userId) {
							$rowData = [];
							$name = $obj->name;
							$rowData[] = $obj->openemis_no;
							$rowData[] = $name;

							// To revisit this part again due to a bug when user add itself in
							if (isset($obj->_joinData->security_role_id)) {
								$securityRoleName = $this->Roles->get($obj->_joinData->security_role_id)->name;
								$this->Session->write($this->registryAlias().'.security_role_id', $securityRoleName);
								$rowData[] = $securityRoleName;
							} else {
								$securityRoleName = $this->Session->read($this->registryAlias().'.security_role_id');
								$rowData[] = $securityRoleName;
							}

							$rowData[] = '';
							$tableCells[] = $rowData;
							$found = true;
							break;
						}
					}
				}
				$associated = $this->request->data("$alias.$key");
				foreach ($associated as $i => $obj) {
					$joinData = $obj['_joinData'];
					if ($joinData['security_user_id'] != $userId) {
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
					} else if ($entity->isNew()) {
						if (!$this->AccessControl->isAdmin()) {
							// If this is a new user group
							$rowData = [];
							$name = $joinData['name'];
							$name .= $Form->hidden("$alias.$key.$i.id", ['value' => $joinData['security_user_id']]);
							$name .= $Form->hidden("$alias.$key.$i._joinData.openemis_no", ['value' => $joinData['openemis_no']]);
							$name .= $Form->hidden("$alias.$key.$i._joinData.name", ['value' => $joinData['name']]);
							$name .= $Form->hidden("$alias.$key.$i._joinData.security_user_id", ['value' => $joinData['security_user_id']]);
							$name .= $Form->hidden("$alias.$key.$i._joinData.security_role_id", ['value' => $joinData['security_role_id']]);
							$rowData[] = $joinData['openemis_no'];
							$rowData[] = $name;
							$rowData[] = __($roleOptions[$joinData['security_role_id']]);
							$rowData[] = $this->getDeleteButton();
							$tableCells[] = $rowData;
						}
					}
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

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->field('no_of_users', ['visible' => ['index' => true]]);
		$this->ControllerAction->setFieldOrder(['name', 'no_of_users']);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$queryParams = $request->query;

		$query->find('notInInstitutions');

		// filter groups by users permission
		if ($this->Auth->user('super_admin') != 1) {
			$userId = $this->Auth->user('id');
			$query->where([
				'OR' => [
					'EXISTS (SELECT `id` FROM `security_group_users` WHERE `security_group_users`.`security_group_id` = `UserGroups`.`id` AND `security_group_users`.`security_user_id` = ' . $userId . ')',
					'UserGroups.created_user_id' => $userId
				]
			]);
		}

		if (!array_key_exists('sort', $queryParams) && !array_key_exists('direction', $queryParams)) {
			$query->order([$this->aliasField('name') => 'asc']);
		}

		$search = $this->ControllerAction->getSearchKey();

		// CUSTOM SEACH - Institution Code, Institution Name, Area Code and Area Name
		$options['auto_search'] = false; // it will append an AND
		if (!empty($search)) {
			$query->find('byInstitutionAreaNameCode', ['search' => $search]);
		}
	}

	public function findByInstitutionAreaNameCode(Query $query, array $options) {
		if (array_key_exists('search', $options)) {
			$search = $options['search'];
			$query
			->join([
				[
					'table' => 'security_group_institutions', 'alias' => 'SecurityGroupInstitutions', 'type' => 'LEFT',
					'conditions' => ['SecurityGroupInstitutions.security_group_id = ' . $this->aliasField('id')]
				],
				[
					'table' => 'institutions', 'alias' => 'Institutions', 'type' => 'LEFT',
					'conditions' => [
						'Institutions.id = ' . 'SecurityGroupInstitutions.institution_id',
					]
				],
				[
					'table' => 'security_group_areas', 'alias' => 'SecurityGroupAreas', 'type' => 'LEFT',
					'conditions' => ['SecurityGroupAreas.security_group_id = ' . $this->aliasField('id')]
				],
				[
					'table' => 'areas', 'alias' => 'Areas', 'type' => 'LEFT',
					'conditions' => [
						'Areas.id = ' . 'SecurityGroupAreas.area_id',
					]
				],
			])
			->where([
					'OR' => [
						['Institutions.code LIKE' => '%' . $search . '%'],
						['Institutions.name LIKE' => '%' . $search . '%'],
						['Areas.code LIKE' => '%' . $search . '%'],
						['Areas.name LIKE' => '%' . $search . '%'],
						[$this->aliasField('name').' LIKE' => '%'.$search.'%']
					]
				]
			)
			->group($this->aliasField('id'))
			;
		}

		return $query;
	}

	public function findNotInInstitutions(Query $query, array $options) {
		$query->where([
			'NOT EXISTS (SELECT `id` FROM `institutions` WHERE `security_group_id` = `UserGroups`.`id`)'
		]);
		return $query;
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// delete all areas if no areas remains in the table
		if (!array_key_exists('areas', $data[$this->alias()])) {
			$data[$this->alias()]['areas'] = [];
		}

		if (!array_key_exists('institutions', $data[$this->alias()])) {
			$data[$this->alias()]['institutions'] = [];
		}

		if (!array_key_exists('users', $data[$this->alias()])) {
			$data[$this->alias()]['users'] = [];
		}

		// in case user has been added with the same role twice, we need to filter it
		$this->filterDuplicateUserRoles($data);

		// To merge in the original modifying user's permission (if any) as the user will not
		// be able to modify their own permission
		$key = 'users';
		$associated = $entity->extractOriginal([$key]);
		$user = $this->Auth->user();
		$userId = $user['id'];
		if ($user['super_admin'] == 1) { // super admin will show all roles
			$userId = null;
		}

		// If not super admin
		if(!is_null($userId)) {
			$userArray = [];
			if (!empty($associated[$key])) {
				foreach ($associated[$key] as $i => $obj) {
					if ($userId == $obj->id) {
						$userArray[$i]['id'] = $obj->id;
						$userArray[$i]['_joinData']['openemis_no'] = $obj->openemis_no;
						$userArray[$i]['_joinData']['name'] = $obj->name;
						$userArray[$i]['_joinData']['security_user_id'] = $obj->id;
						$userArray[$i]['_joinData']['security_role_id'] = $obj->_joinData->security_role_id;
					}
				}
			}
			$data[$this->alias()][$key] = array_merge($userArray, $data[$this->alias()][$key]);
		}

		// Required by patchEntity for associated data
		$newOptions = [];

		// The association can be added if it is an add action
		if ($this->action == 'add') {
			$newOptions['associated'] = ['Areas', 'Institutions', 'Users'];
		} 
		// For edit function, the user role is save from the edit after save logic as users cannot be save properly using associated method
		else {
			$newOptions['associated'] = ['Areas', 'Institutions'];
		}

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

	// same logic also in SystemGroups, may consider moving it into a behavior
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

	// also exists in SystemGroups
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

	public function findByUser(Query $query, array $options) {
		$userId = $options['userId'];
		$alias = $this->alias();

		$query
		->join([
			[
				'table' => 'security_group_users',
				'alias' => 'SecurityGroupUsers',
				'type' => 'LEFT',
				'conditions' => ["SecurityGroupUsers.security_group_id = $alias.id"]
			]
		])
		->where([
			'OR' => [
				"$alias.created_user_id" => $userId,
				'SecurityGroupUsers.security_user_id' => $userId
			]
		])
		->group([$this->aliasField('id')]);
		return $query;
	}

	public function onGetNoOfUsers(Event $event, Entity $entity) {
		$id = $entity->id;

		$GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
		$count = $GroupUsers->findAllBySecurityGroupId($id)->count();

		return $count;
	}

	public function ajaxAreaAutocomplete() {
		$this->controller->autoRender = false;
		$this->ControllerAction->autoRender = false;

		if ($this->request->is(['ajax'])) {
			$term = $this->request->query['term'];
			$data = $this->Areas->autocomplete($term);
			echo json_encode($data);
			die;
		}
	}

	public function ajaxInstitutionAutocomplete() {
		$this->controller->autoRender = false;
		$this->ControllerAction->autoRender = false;

		if ($this->request->is(['ajax'])) {
			$term = $this->request->query['term'];
			$data = $this->Institutions->autocomplete($term);
			echo json_encode($data);
			die;
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
