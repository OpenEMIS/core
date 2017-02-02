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
class UsersTable extends ControllerActionTable {
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
		$this->toggle('remove', false);
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('address_area_id', ['visible' => false]);
		$this->field('birthplace_area_id', ['visible' => false]);
		$this->field('gender_id', ['type' => 'hidden']);
		$this->field('preferred_name', ['visible' => false]);
		$this->field('address', ['visible' => false]);
		$this->field('postal_code', ['visible' => false]);
		$this->field('status', ['visible' => false]);
		$this->field('super_admin', ['visible' => false]);
		$this->field('date_of_death', ['visible' => false]);
		$this->field('photo_name', ['visible' => false]);
		$this->field('photo_content', ['visible' => false]);
		$this->field('date_of_death', ['visible' => false]);
		$this->field('is_student', ['visible' => false]);
		$this->field('is_staff', ['visible' => false]);
		$this->field('is_guardian', ['visible' => false]);
		$this->field('external_reference', ['visible' => false]);

		// $this->ControllerAction->field('openemis_no', ['type' => 'readonly']);
		$userId = $this->paramsEncode(['id' => $this->Auth->user('id')]);
		if ($userId != $this->paramsPass(0) && $this->action != 'password') { // stop user from navigating to other profiles
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

	public function viewBeforeAction(Event $event, ArrayObject $extra) {
		$this->field('roles', [
			'type' => 'role_table',
			'valueClass' => 'table-full-width',
			'visible' => ['index' => false, 'view' => true, 'edit' => false],
			'order' => 100
		]);

		$this->setFieldOrder(['username', 'openemis_no', 'first_name', 'middle_name', 'third_name', 'last_name', 'date_of_birth', 'nationality_id', 'identity_type_id', 'identity_number', 'last_login', 'preferred_language', 'modified_user_id', 'modified', 'created_user_id', 'created', 'roles']);
	}

	public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$query->contain(['Roles']);
	}

	public function addEditBeforeAction(Event $event, ArrayObject $extra) {
		$this->field('username', ['visible' => false]);
		$this->field('openemis_no', ['visible' => false]);
		$this->field('date_of_birth', [
				'date_options' => [
					'endDate' => date('d-m-Y', strtotime("-2 year"))
				],
				'default_date' => false,
			]
		);
		$this->field('last_login', ['visible' => false]);

		if ($this->action == 'edit') {
			$this->field('identity_number', ['type' => 'readonly']);
		}
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
	{
		$this->field('preferred_language', ['type' => 'select', 'entity' => $entity]);
		$this->field('nationality_id', ['type' => 'select']);
		$this->field('identity_type_id', ['type' => 'select']);
		$this->setFieldOrder(['first_name', 'middle_name', 'third_name', 'last_name', 'date_of_birth', 'preferred_language', 'nationality_id', 'identity_type_id', 'identity_number']);
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

	public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
	{
		// To change the language of the UI
		$url = $this->url('view');
		$url['lang'] = $entity->preferred_language;
		return $this->controller->redirect($url);
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
}
