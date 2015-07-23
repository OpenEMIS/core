<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use App\Model\Table\AppTable;

class AccountsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('security_users');
		parent::initialize($config);

		$this->belongsToMany('Roles', [
			'className' => 'Security.SecurityRoles',
			'joinTable' => 'security_group_users',
			'foreignKey' => 'security_user_id',
			'targetForeignKey' => 'security_role_id',
			'through' => 'Security.SecurityGroupUsers',
			'dependent' => true
		]);
	}

	public function validationDefault(Validator $validator) {
		return $validator
			->requirePresence('gender_id', 'create')
			->add('password' , [
				'ruleMinLength' => [
					'rule' => ['minLength', 6],
					'on' => 'update'
				]
			])
			->add('retype_password' , [
				'ruleCompare' => [
					'rule' => ['comparePasswords', 'password'],
					'on' => 'update'
				]
			])
			;
	}

	private function setTabElements() {
		if ($this->controller->name == 'Institutions') return;

		$plugin = $this->controller->plugin;
		$name = $this->controller->name;

		// needs a better solution to handle buttons
		$id = $this->ControllerAction->buttons['view']['url'][0];
		if ($id=='view' || $id=='edit') {
			if (isset($this->ControllerAction->buttons['view']['url'][1])) {
				$id = $this->ControllerAction->buttons['view']['url'][1];
			}
		}

		$tabElements = [
			'Details' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'view', $id],
				'text' => __('Details')
			],
			'Accounts' => [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Accounts', 'view', $id],
				'text' => __('Account')	
			]
		];

		if (!in_array($this->controller->name, ['Students', 'Staff', 'Guardians', 'Institutions'])) {
			$tabElements['Details'] = [
				'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Users', 'view', $id],
				'text' => __('Details')
			];
		}

		$this->controller->set('selectedAction', $this->alias);
		$this->controller->set('tabElements', $tabElements);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		return $events;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'view') {
			if ($toolbarButtons->offsetExists('back')) {
				unset($toolbarButtons['back']);
			}
		}
	}

	public function afterAction(Event $event) {
		$this->setTabElements();
	}

	public function beforeAction(Event $event) {
		$fieldsNeeded = ['username','password', 'last_login'];
		foreach ($this->fields as $key => $value) {
			if (!in_array($key, $fieldsNeeded)) {
				$this->fields[$key]['visible'] = false;
			} else {
				$this->fields[$key]['visible'] = true;
			}
		}

		$this->ControllerAction->field('last_login', ['visible' => ['view' => true, 'edit' => false]]);
		$this->ControllerAction->field('password', ['type' => 'password', 'visible' => ['view' => false, 'edit' => true]]);

		$this->ControllerAction->setFieldOrder(['username', 'password']);

		if (strtolower($this->action) != 'index') {
			$this->Navigation->addCrumb($this->getHeader($this->action));
		}
	}

	public function viewBeforeAction(Event $event) {
		$this->ControllerAction->field('roles', [
			'type' => 'role_table', 
			'valueClass' => 'table-full-width',
			'visible' => ['index' => false, 'view' => true, 'edit' => false]
		]);
		$this->ControllerAction->setFieldOrder(['username', 'last_login', 'roles']);
	}

	public function viewBeforeQuery(Event $event, Query $query) {
		$query->contain([], true);
		$query->contain(['Roles']);
	}

	public function onGetRoleTableElement(Event $event, $action, $entity, $attr, $options=[]) {
		$tableHeaders = [__('Groups'), __('Roles')];
		$tableCells = [];
		$alias = $this->alias();
		$key = 'roles';

		$Group = TableRegistry::get('Security.SecurityGroups');

		if ($action == 'view') {
			$associated = $entity->extractOriginal([$key]);
			if (!empty($associated[$key])) {
				foreach ($associated[$key] as $i => $obj) {
					$groupId = $obj['_joinData']->security_group_id;
					$groupEntity = $Group->get($groupId);

					$rowData = [];
					$rowData[] = $groupEntity->name;
					$rowData[] = $obj->name;
					$tableCells[] = $rowData;
				}
			}
		}
		$attr['tableHeaders'] = $tableHeaders;
    	$attr['tableCells'] = $tableCells;

		return $event->subject()->renderElement('User.Accounts/' . $key, ['attr' => $attr]);
	}

	public function editBeforeAction(Event $event)  {
		$this->ControllerAction->field('retype_password', ['type' => 'password']);

		$this->ControllerAction->setFieldOrder(['username', 'password', 'retype_password']);
	}

	public function editBeforeQuery(Event $event, Query $query) {
		// not retrieving password so the field wil be empty. not needed anyway.
		$query->select([$this->primaryKey(), 'username']);
	}
}