<?php 
namespace User\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class AccountBehavior extends Behavior {
	private $isInstitution = false;
	private $userRole = null;

	public function initialize(array $config) {
		$this->_table->table('security_users');
		$this->_table->entityClass('User.User');
		parent::initialize($config);
		// is_institution
		$this->userRole = (array_key_exists('userRole', $config))? $config['userRole']: null;
		$this->isInstitution = (array_key_exists('isInstitution', $config))? $config['isInstitution']: null;

		$this->_table->belongsToMany('Roles', [
			'className' => 'Security.SecurityRoles',
			'joinTable' => 'security_group_users',
			'foreignKey' => 'security_user_id',
			'targetForeignKey' => 'security_role_id',
			'through' => 'Security.SecurityGroupUsers',
			'dependent' => true
		]);
	}

	public function getAccountValidation(Validator $validator) {
		$this->_table->setValidationCode('username.ruleUnique', 'User.Accounts');
		$this->_table->setValidationCode('password.ruleMinLength', 'User.Accounts');
		$this->_table->setValidationCode('retype_password.ruleCompare', 'User.Accounts');
		return $validator
			->requirePresence('gender_id', 'create')
			->add('username', [
				'ruleUnique' => [
					'rule' => 'validateUnique',
					'provider' => 'table',
				]
			])
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

	private function setupTabElements($entity) {
		$id = !is_null($this->_table->request->query('id')) ? $this->_table->request->query('id') : 0;

		$options = [
			'userRole' => Inflector::singularize($this->userRole),
			'action' => $this->_table->action,
			'id' => $id,
			'userId' => $entity->id
		];

		$tabElements = $this->_table->controller->getUserTabElements($options);

		if ($this->_table->action != 'add') {
			if ($this->isInstitution) {
				// url of tabElements is build in Institution->getUserTabElements()
			} else {
				foreach ($tabElements as $key => $value) {
					end($tabElements[$key]['url']);
					$tabElements[$key]['url'][key($tabElements[$key]['url'])] = $entity->id;
				}
			}
		}

		$this->_table->controller->set('tabElements', $tabElements);
		$this->_table->controller->set('selectedAction', $this->_table->alias());
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->_table->ControllerAction->field('roles', [
			'type' => 'role_table', 
			'valueClass' => 'table-full-width',
			'visible' => ['index' => false, 'view' => true, 'edit' => false]
		]);
		$this->_table->ControllerAction->setFieldOrder(['username', 'last_login', 'roles']);

		$this->afterActionCode($event, $entity);
	}

	public function editAfterAction(Event $event, Entity $entity)  {
		$this->_table->ControllerAction->field('retype_password', ['type' => 'password', 'attr' => ['value' => '']]);
		$this->_table->ControllerAction->setFieldOrder(['username', 'password', 'retype_password']);

		$this->afterActionCode($event, $entity);
	}

	// called manually cos need to use $entity
	private function afterActionCode(Event $event, Entity $entity) {
		$fieldsNeeded = ['username','password', 'roles', 'retype_password'];
		foreach ($this->_table->fields as $key => $value) {
			if (!in_array($key, $fieldsNeeded)) {
				$this->_table->fields[$key]['visible'] = false;
			} else {
				$this->_table->fields[$key]['visible'] = true;
			}
		}

		$this->_table->ControllerAction->field('last_login', ['visible' => ['view' => true, 'edit' => false]]);
		$this->_table->ControllerAction->field('password', ['type' => 'password', 'visible' => ['view' => false, 'edit' => true], 'attr' => ['value' => '']]);



		$this->_table->ControllerAction->setFieldOrder(['username', 'password']);

		if (strtolower($this->_table->action) != 'index') {
			if (!$this->isInstitution) {
				$this->_table->Navigation->addCrumb($this->_table->getHeader($this->_table->action));
			}
			
		}

		$this->setupTabElements($entity);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		// $events['ControllerAction.Model.afterAction'] = 'afterAction';
		$events['ControllerAction.Model.edit.afterAction'] = 'editAfterAction';
		$events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
		$events['ControllerAction.Model.view.beforeQuery'] = 'viewBeforeQuery';
		$events['ControllerAction.Model.edit.afterAction'] = 'editAfterAction';
		$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		return $events;
	}

	public function viewBeforeQuery(Event $event, Query $query) {
		$options['auto_contain'] = false;
		$query->contain(['Roles']);
	}


	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'view') {
			if ($toolbarButtons->offsetExists('back')) {
				unset($toolbarButtons['back']);
			}
		}
	}

	public function onGetRoleTableElement(Event $event, $action, $entity, $attr, $options=[]) {
		$tableHeaders = [__('Groups'), __('Roles')];
		$tableCells = [];
		$alias = $this->_table->alias();
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
}
