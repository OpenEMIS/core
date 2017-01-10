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

// this file is used solely for Preferences/Users
class UsersTable extends AppTable {
	private $loginLanguages = [];
	public function initialize(array $config) {
		$this->table('security_users');
		$this->entityClass('User.User');
		parent::initialize($config);

		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->hasMany('SpecialNeeds', ['className' => 'User.SpecialNeeds', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
		$this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

		$this->belongsToMany('Roles', [
			'className' => 'Security.SecurityRoles',
			'joinTable' => 'security_group_users',
			'foreignKey' => 'security_user_id',
			'targetForeignKey' => 'security_role_id',
			'through' => 'Security.SecurityGroupUsers',
			'dependent' => true
		]);
		$ConfigItemOptionsTable = TableRegistry::get('Configuration.ConfigItemOptions');
		$this->loginLanguages = $ConfigItemOptionsTable->find('list', [
				'keyField' => 'value',
				'valueField' => 'option'
			])
			->where([$ConfigItemOptionsTable->aliasField('option_type') => 'language'])
			->toArray();
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
		$userId = $this->paramsEncode(['id' => $this->Auth->user('id')]);
		if ($userId != current($this->ControllerAction->paramsPass()) && $this->action != 'password') { // stop user from navigating to other profiles
			$event->stopPropagation();
			return $this->controller->redirect(['plugin' => null, 'controller' => $this->controller->name, 'action' => 'view', $userId]);
		}

		$tabElements = $this->controller->getUserTabElements();

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'General');
	}

	public function onGetPreferredLanguage(Event $event, Entity $entity)
	{
		if (isset($this->loginLanguages[$entity->preferred_language])) {
			return $this->loginLanguages[$entity->preferred_language];
		} else {
			return $entity->preferred_language;
		}
	}

	public function viewBeforeAction(Event $event) {
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

	public function addEditBeforeAction(Event $event) {
		$this->ControllerAction->field('username', ['visible' => false]);
		$this->ControllerAction->field('openemis_no', ['visible' => false]);
		$this->ControllerAction->field('date_of_birth', [
				'date_options' => [
					'endDate' => date('d-m-Y', strtotime("-2 year"))
				],
				'default_date' => false,
			]
		);
		$this->ControllerAction->field('last_login', ['visible' => false]);

		if ($this->action == 'edit') {
			$this->ControllerAction->field('identity_number', ['visible' => false]);
		}
	}

	public function editAfterAction(Event $event, Entity $entity)
	{
		$this->ControllerAction->field('preferred_language', ['type' => 'select', 'entity' => $entity]);
	}

	public function onUpdateFieldPreferredLanguage(Event $event, array $attr, $action, Request $request)
	{
		$session = $this->request->session();
		if ($session->read('System.language_menu')) {
			$attr['options'] = $this->loginLanguages;
		} else {
			$attr['type'] = 'disabled';
			$entity = $attr['entity'];
			$attr['attr']['value'] = $this->loginLanguages[$entity->preferred_language];
		}

		return $attr;
	}

	public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions)
	{
		// To change the language of the UI
		$url = $this->ControllerAction->url('view');
		$url['lang'] = $entity->preferred_language;
		return $this->controller->redirect($url);
	}

	public function password() {
		$this->controller->set('selectedAction', 'Account');

		$userId = $this->Auth->user('id');
		$this->ControllerAction->edit($userId);

		$this->ControllerAction->field('username', ['visible' => true, 'type' => 'readonly']);
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

		$this->ControllerAction->renderView('/ControllerAction/edit');
	}

	public function onGetRoleTableElement(Event $event, $action, $entity, $attr, $options=[]) {
		$tableHeaders = [__('Groups'), __('Roles')];
		$tableCells = [];
		$key = 'roles';
		if ($action == 'view') {
			$GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
			$groupUserRecords = $GroupUsers->find()
				->matching('SecurityGroups')
				->matching('SecurityRoles')
				->where([$GroupUsers->aliasField('security_user_id') => $entity->id])
				->group([
					$GroupUsers->aliasField('security_group_id'),
					$GroupUsers->aliasField('security_role_id')
				])
				->select(['group_name' => 'SecurityGroups.name', 'role_name' => 'SecurityRoles.name'])
				->all();
			foreach ($groupUserRecords as $obj) {
				$rowData = [];
				$rowData[] = $obj->group_name;
				$rowData[] = $obj->role_name;
				$tableCells[] = $rowData;
			}
		}
		$attr['tableHeaders'] = $tableHeaders;
		$attr['tableCells'] = $tableCells;

		return $event->subject()->renderElement('User.Accounts/' . $key, ['attr' => $attr]);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		$BaseUsers = TableRegistry::get('User.Users');
		return $BaseUsers->setUserValidation($validator, $this);
	}

	public function validationPassword(Validator $validator) {
		$retypeCompareField = 'new_password';

		$this->setValidationCode('username.ruleMinLength', 'User.Accounts');
        $this->setValidationCode('username.ruleUnique', 'User.Accounts');
		$this->setValidationCode('username.ruleAlphanumeric', 'User.Accounts');
		$this->setValidationCode('retype_password.ruleCompare', 'User.Accounts');
		return $validator
			->add('username', [
                'ruleMinLength' => [
                    'rule' => ['minLength', 6]
                ],
				'ruleUnique' => [
					'rule' => 'validateUnique',
					'provider' => 'table',
				],
				'ruleAlphanumeric' => [
				    'rule' => 'alphanumeric',
				]
			])
			// password validation now in behavior
			->add('retype_password' , [
				'ruleCompare' => [
					'rule' => ['comparePasswords', $retypeCompareField],
					'on' => 'update'
				]
			])
			;
	}


	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		return $events;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		switch ($action) {
			case 'view':
				if ($toolbarButtons->offsetExists('edit')) {
					$toolbarButtons->exchangeArray(['edit' => $toolbarButtons['edit']]);
				} else {
					$toolbarButtons->exchangeArray([]);
				}

				break;

			case 'edit':
				if ($toolbarButtons->offsetExists('back')) {
					$toolbarButtons->exchangeArray(['back' => $toolbarButtons['back']]);
				} else {
					$toolbarButtons->exchangeArray([]);
				}
				break;
		}
	}

	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
	{
		if ($field == 'identity_number') {
			$IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
			$defaultIdentity = $IdentityType->getDefaultEntity();
			if ($defaultIdentity) {
				$value = $defaultIdentity->name;
			}
			return (!empty($value)) ? $value : parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
		} else {
			return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
		}
	}
}
