<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use App\Model\Table\AppTable;

use App\Model\Traits\OptionsTrait;

class UsersTable extends AppTable
{
	use OptionsTrait;
	public function initialize(array $config)
	{
		$this->table('security_users');
		parent::initialize($config);
		$this->entityClass('User.User');

		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
		$this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
		$this->belongsTo('IdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

		$this->belongsToMany('Roles', [
			'className' => 'Security.SecurityRoles',
			'joinTable' => 'security_group_users',
			'foreignKey' => 'security_user_id',
			'targetForeignKey' => 'security_role_id',
			'through' => 'Security.SecurityGroupUsers',
			'dependent' => true
		]);

		$this->hasMany('Identities', 		['className' => 'User.Identities',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('Nationalities', 	['className' => 'User.UserNationalities',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('SpecialNeeds', 		['className' => 'User.SpecialNeeds',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('Contacts', 			['className' => 'User.Contacts',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('Attachments', 		['className' => 'User.Attachments',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('BankAccounts', 		['className' => 'User.BankAccounts',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('Comments', 			['className' => 'User.Comments',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('Languages', 		['className' => 'User.UserLanguages',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('Awards', 			['className' => 'User.Awards',			'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('Logins', 			['className' => 'Security.SecurityUserLogins', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');
		$this->addBehavior('Security.UserCascade'); // for cascade delete on user related tables
	}

	public function implementedEvents()
	{
		$events = parent::implementedEvents();
		$events['Model.Students.afterSave'] = 'studentsAfterSave';
		return $events;
	}

	public function studentsAfterSave(Event $event, Entity $entity)
	{
		if ($entity->isNew()) {
			$this->updateAll(['is_student' => 1], ['id' => $entity->student_id]);
		}
	}

	// autocomplete used for UserGroups
	public function autocomplete($search)
	{
		$search = sprintf('%s%%', $search);

		$list = $this
			->find()
			->select([
				$this->aliasField('openemis_no'),
				$this->aliasField('first_name'),
				$this->aliasField('middle_name'),
				$this->aliasField('third_name'),
				$this->aliasField('last_name'),
				$this->aliasField('preferred_name'),
				$this->aliasField('id')
			])
			->where([
				'OR' => [
					$this->aliasField('openemis_no') . ' LIKE' => $search,
					$this->aliasField('first_name') . ' LIKE' => $search,
					$this->aliasField('middle_name') . ' LIKE' => $search,
					$this->aliasField('third_name') . ' LIKE' => $search,
					$this->aliasField('last_name') . ' LIKE' => $search
				]
			])
			->order([$this->aliasField('first_name')])
			->limit(100)
			->all();

		$data = array();
		foreach($list as $obj) {
			$data[] = [
				'label' => sprintf('%s - %s', $obj->openemis_no, $obj->name),
				'value' => $obj->id
			];
		}
		return $data;
	}

	public function beforeAction(Event $event)
	{
		$this->fields['photo_content']['visible'] = false;
		$this->fields['address']['visible'] = false;
		$this->fields['postal_code']['visible'] = false;
		$this->fields['address_area_id']['visible'] = false;
		$this->fields['birthplace_area_id']['visible'] = false;
		$this->fields['nationality_id']['type'] = 'readonly';
		$this->fields['identity_type_id']['type'] = 'readonly';

		if (in_array($this->action, ['add'])) {
			$this->fields['username']['visible'] = true;
			$this->fields['password']['visible'] = true;
			$this->fields['password']['type'] = 'password';
			$this->fields['password']['attr']['value'] = '';
			$this->fields['password']['attr']['autocomplete'] = 'off';
		}

		if ($this->action == 'edit') {
			$this->fields['last_login']['visible'] = false;
		}

		$this->ControllerAction->field('status', ['visible' => true, 'options' => $this->getSelectOptions('general.active')]);
		$this->ControllerAction->setFieldOrder([
			'openemis_no', 'first_name', 'middle_name', 'third_name', 'last_name', 'preferred_name', 'gender_id', 'date_of_birth', 'status', 'username', 'password'
		]);
	}

	public function indexBeforeAction(Event $event)
	{
		$this->fields['first_name']['visible'] = false;
		$this->fields['middle_name']['visible'] = false;
		$this->fields['third_name']['visible'] = false;
		$this->fields['preferred_name']['visible'] = false;
		$this->fields['last_name']['visible'] = false;
		$this->fields['gender_id']['visible'] = false;
		$this->fields['date_of_birth']['visible'] = false;
		$this->fields['username']['visible'] = true;

		$this->ControllerAction->field('name');
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options)
	{
		$options['auto_search'] = false;
		$query->find('notSuperAdmin');

		$search = $this->ControllerAction->getSearchKey();

		if (!empty($search)) {
			$query = $this->addSearchConditions($query, ['searchTerm' => $search, 'searchByUserName' => true]);
		}
	}

	public function findNotSuperAdmin(Query $query, array $options)
	{
		return $query->where([$this->aliasField('super_admin') => 0]);
	}

	public function viewBeforeAction(Event $event)
	{
		$this->ControllerAction->field('roles', [
			'type' => 'role_table',
			'order' => 69,
			'valueClass' => 'table-full-width',
			'visible' => ['index' => false, 'view' => true, 'edit' => false]
		]);
	}

	public function viewEditBeforeQuery(Event $event, Query $query)
	{
		$query->find('notSuperAdmin');
		$query->contain(['MainNationalities', 'IdentityTypes']);
	}

	public function viewBeforeQuery(Event $event, Query $query)
	{
		$options['auto_contain'] = false;
		$query->contain(['Roles']);
	}

	public function viewAfterAction(Event $event, Entity $entity)
	{
		$this->setupTabElements(['id' => $entity->id]);
	}

	private function setupTabElements($options)
	{
		$this->controller->set('selectedAction', 'Securities');
		$this->controller->set('tabElements', $this->controller->getUserTabElements($options));
	}

	public function onGetRoleTableElement(Event $event, $action, $entity, $attr, $options=[])
	{
		$tableHeaders = [__('Groups'), __('Roles')];
		$tableCells = [];
		$alias = $this->alias();
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
				->select(['group_name' => 'SecurityGroups.name', 'role_name' => 'SecurityRoles.name', 'group_id' => 'SecurityGroups.id'])
				->all();
			foreach ($groupUserRecords as $obj) {
				$rowData = [];
				$url = [
					'plugin' => $this->controller->plugin,
					'controller' => $this->controller->name,
					'view',
					$this->paramsEncode(['id' => $obj->group_id])
				];
				if (!empty($groupEntity->institution)) {
					$url['action'] = 'SystemGroups';
				} else {
					$url['action'] = 'UserGroups';
				}
				$rowData[] = $event->subject()->Html->link($obj->group_name, $url);

				$rowData[] = $obj->role_name; // role name
				$tableCells[] = $rowData;
			}
		}
		$attr['tableHeaders'] = $tableHeaders;
    	$attr['tableCells'] = $tableCells;

		return $event->subject()->renderElement('User.Accounts/' . $key, ['attr' => $attr]);
	}

	public function addBeforeAction(Event $event)
	{
		$uniqueOpenemisId = $this->getUniqueOpenemisId(['model'=>Inflector::singularize('User')]);

		// first value is for the hidden field value, the second value is for the readonly value
		$this->ControllerAction->field('openemis_no', ['type' => 'readonly', 'value' => $uniqueOpenemisId, 'attr' => ['value' => $uniqueOpenemisId]]);

		//this field value will be generated automatically when identity details changed.
		$this->ControllerAction->field('identity_number', ['type' => 'hidden']);
	}

	public function editAfterAction(Event $event, Entity $entity)
	{
		$this->fields['identity_number']['type'] = 'readonly'; //cant edit identity_number field value as its value is auto updated.
		$this->fields['nationality_id']['attr']['value'] = $entity->has('main_nationality') ? $entity->main_nationality->name : '';
		$this->fields['identity_type_id']['attr']['value'] = $entity->has('identity_type') ? $entity->identity_type->name : '';
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
	{
		// not saving empty passwords
		if (empty($data[$this->alias()]['password'])) {
			unset($data[$this->alias()]['password']);
		}
	}

	public function validationDefault(Validator $validator)
	{
		$validator = parent::validationDefault($validator);
		$BaseUsers = TableRegistry::get('User.Users');
		return $BaseUsers->setUserValidation($validator, $this);
	}

	public function isAdmin($userId)
	{
		return $this->get($userId)->super_admin;
	}

	public function getModelAlertData($threshold)
	{
		$thresholdArray = json_decode($threshold, true);

		$operandConditions = [
			// only cater for age is more than and equal to threshold value.
			2 => ('TIMESTAMPDIFF(YEAR, ' . $this->aliasField('date_of_birth') . ', NOW())' . ' >= ' . $thresholdArray['value']), // after
		];

		// will do the comparison with threshold when retrieving the absence data
		$licenseData = $this->find()
			->select([
				'id',
				'openemis_no',
				'first_name',
				'middle_name',
				'third_name',
				'last_name',
				'preferred_name',
				'email',
				'address',
				'postal_code',
				'date_of_birth',
			])
			->where([
				$this->aliasField('date_of_birth') . ' IS NOT NULL',
				$operandConditions[$thresholdArray['operand']]
			])

			->hydrate(false)
			;

		return $licenseData->toArray();
	}
}
