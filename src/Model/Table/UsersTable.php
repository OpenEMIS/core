<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\UserTrait;
use Cake\Datasource\Exception\RecordNotFoundException;

class UsersTable extends AppTable {
	public function initialize(array $config) {
		$this->table('security_users');
		$this->entityClass('User.User');
		parent::initialize($config);

		$this->belongsTo('Genders', ['className' => 'User.Genders']);

		$this->belongsToMany('Roles', [
			'className' => 'Security.SecurityRoles',
			'joinTable' => 'security_group_users',
			'foreignKey' => 'security_user_id',
			'targetForeignKey' => 'security_role_id',
			'through' => 'Security.SecurityGroupUsers',
			'dependent' => true
		]);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('address_area_id', ['visible' => false]);
		$this->ControllerAction->field('birthplace_area_id', ['visible' => false]);
		$this->ControllerAction->field('gender_id', ['type' => 'hidden']);
		$this->ControllerAction->field('preferred_name', ['visible' => false]);
		$this->ControllerAction->field('address', ['visible' => false]);
		$this->ControllerAction->field('postal_code', ['visible' => false]);
		$this->ControllerAction->field('status', ['visible' => false]);
		$this->ControllerAction->field('super_admin', ['visible' => false]);
		$this->ControllerAction->field('date_of_death', ['visible' => false]);
		$this->ControllerAction->field('photo_name', ['visible' => false]);
		$this->ControllerAction->field('photo_content', ['visible' => false]);
		$this->ControllerAction->field('date_of_death', ['visible' => false]);
		$this->ControllerAction->field('is_student', ['visible' => false]);
		$this->ControllerAction->field('is_staff', ['visible' => false]);
		$this->ControllerAction->field('is_guardian', ['visible' => false]);
		// $this->ControllerAction->field('openemis_no', ['type' => 'readonly']);

		$controller = $this->controller;
		$userId = $this->Auth->user('id');
		$tabElements = [
			'account' => [
				'url' => ['plugin' => null, 'controller' => $controller->name, 'action' => 'view', $userId],
				'text' => __('Account')
			],
			'password' => [
				'url' => ['plugin' => null, 'controller' => $controller->name, 'action' => 'Users', 'password'],
				'text' => __('Password')
			]
		];

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'account');
	}

	public function viewBeforeAction(Event $event) {
		$userId = $this->Auth->user('id');
		if ($userId != $this->request->pass[0]) { // stop user from navigating to other profiles
			$event->stopPropagation();
			return $this->controller->redirect(['plugin' => null, 'controller' => $this->controller->name, 'action' => 'view', $userId]);
		}
		$this->ControllerAction->field('roles', [
			'type' => 'role_table', 
			'valueClass' => 'table-full-width',
			'visible' => ['index' => false, 'view' => true, 'edit' => false],
			'order' => 100
		]);
	}

	public function viewBeforeQuery(Event $event, Query $query) {
		$query->contain(['Roles']);
	}

	public function password() {
		$this->controller->set('selectedAction', 'password');
		$userId = $this->Auth->user('id');
		$entity = $this->get($userId);
		$entity->password = '';

		$this->ControllerAction->field('username', ['type' => 'readonly']);
		$this->ControllerAction->field('openemis_no', ['visible' => false]);
		$this->ControllerAction->field('first_name', ['visible' => false]);
		$this->ControllerAction->field('middle_name', ['visible' => false]);
		$this->ControllerAction->field('third_name', ['visible' => false]);
		$this->ControllerAction->field('last_name', ['visible' => false]);
		$this->ControllerAction->field('date_of_birth', ['visible' => false]);
		$this->ControllerAction->field('last_login', ['visible' => false]);
		$this->ControllerAction->field('password', ['type' => 'password', 'visible' => true, 'attr' => ['value' => '']]);
		$this->ControllerAction->field('new_password', ['type' => 'password', 'order' => 60, 'attr' => ['value' => '']]);
		$this->ControllerAction->field('retype_password', ['type' => 'password', 'order' => 61, 'attr' => ['value' => '']]);

		$request = $this->request;
		if ($request->is(['post', 'put'])) {
			$request->data['username'] = $request->data[$this->alias()]['username'];
			$request->data['password'] = $request->data[$this->alias()]['password'];

			$user = $this->Auth->identify();
			if ($user != false) {
				$newPassword = $request->data[$this->alias()]['new_password'];
				$retypePassword = $request->data[$this->alias()]['retype_password'];

				if ($newPassword === $retypePassword) {
					if (strlen($newPassword) >= 6) {
						$entity->password = $newPassword;
						if ($this->save($entity)) {
							$this->Alert->success('general.edit.success');
							$action = ['plugin' => false, 'controller' => $this->controller->name, 'action' => 'Users', 'view', $entity->id];
							return $this->controller->redirect($action);
						} else {
							$this->Alert->error('general.error');
						}
					} else {
						$this->Alert->error('User.Users.password.ruleMinLength');
					}
				} else {
					$this->Alert->error('User.Users.retype_password.ruleCompare');
				}
			} else {
				$this->Alert->error('User.Users.password.ruleChangePassword');
			}
		}

		// pr($entity);
		$this->controller->set('data', $entity);
		$this->ControllerAction->renderView('/ControllerAction/edit');
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
					try {
						$groupEntity = $Group->get($groupId);
						$rowData = [];
						$rowData[] = $groupEntity->name;
						$rowData[] = $obj->name;
						$tableCells[] = $rowData;
					} catch (RecordNotFoundException $ex) {
						$this->log($groupId . ' is missing in security_groups', 'error');
					}
				}
			}
		}
		$attr['tableHeaders'] = $tableHeaders;
    	$attr['tableCells'] = $tableCells;

		return $event->subject()->renderElement('User.Accounts/' . $key, ['attr' => $attr]);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->add('first_name', [
					'ruleCheckIfStringGotNoNumber' => [
						'rule' => 'checkIfStringGotNoNumber',
					],
					'ruleNotBlank' => [
						'rule' => 'notBlank',
					]
				])
			->allowEmpty('middle_name')
			->allowEmpty('third_name')
			->add('last_name', [
					'ruleCheckIfStringGotNoNumber' => [
						'rule' => 'checkIfStringGotNoNumber',
					]
				])
			->allowEmpty('preferred_name')
			->add('openemis_no', [
					'ruleUnique' => [
						'rule' => 'validateUnique',
						'provider' => 'table',
					]
				])
			->add('username', [
				'ruleUnique' => [
					'rule' => 'validateUnique',
					'provider' => 'table',
				],
				'ruleAlphanumeric' => [
				    'rule' => 'alphanumeric',
				]
			])
			->allowEmpty('username')
			->allowEmpty('password')
			->add('address', [])
			->allowEmpty('photo_content')
			;
		return $validator;
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {   
		$toolbarButtons->exchangeArray([]);
	}
}
