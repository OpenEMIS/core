<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use App\Model\Table\AppTable;

use App\Model\Traits\OptionsTrait;

class UsersTable extends AppTable {
	use OptionsTrait;
	public function initialize(array $config) {
		$this->table('security_users');
		parent::initialize($config);
		$this->entityClass('User.User');

		$this->belongsTo('Genders', ['className' => 'User.Genders']);

		$this->belongsToMany('Roles', [
			'className' => 'Security.SecurityRoles',
			'joinTable' => 'security_group_users',
			'foreignKey' => 'security_user_id',
			'targetForeignKey' => 'security_role_id',
			'through' => 'Security.SecurityGroupUsers',
			'dependent' => true
		]);

		$this->hasMany('Identities', 		['className' => 'User.Identities',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('Nationalities', 	['className' => 'User.Nationalities',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('SpecialNeeds', 		['className' => 'User.SpecialNeeds',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('Contacts', 			['className' => 'User.Contacts',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('Attachments', 		['className' => 'User.Attachments',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('BankAccounts', 		['className' => 'User.BankAccounts',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('Comments', 			['className' => 'User.Comments',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('Languages', 		['className' => 'User.UserLanguages',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->hasMany('Awards', 			['className' => 'User.Awards',			'foreignKey' => 'security_user_id', 'dependent' => true]);

		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');
		$this->addBehavior('Security.UserCascade'); // for cascade delete on user related tables
	}

	// autocomplete used for UserGroups
	public function autocomplete($search) {
		$search = sprintf('%%%s%%', $search);

		$list = $this
			->find()
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

	public function beforeAction(Event $event) {
		$this->fields['photo_content']['visible'] = false;
		$this->fields['address']['visible'] = false;
		$this->fields['postal_code']['visible'] = false;
		$this->fields['address_area_id']['visible'] = false;
		$this->fields['birthplace_area_id']['visible'] = false;

		if ($this->action != 'index' && $this->action != 'view') {
			$this->fields['username']['visible'] = true;
			$this->fields['password']['visible'] = true;
			$this->fields['password']['type'] = 'password';
			$this->fields['password']['attr']['value'] = '';
		}
		if ($this->action == 'edit') {
			$this->fields['last_login']['visible'] = false;
		}

		$this->ControllerAction->field('status', ['visible' => true, 'options' => $this->getSelectOptions('general.active')]);
	}

	public function indexBeforeAction(Event $event) {
		$this->fields['first_name']['visible'] = false;
		$this->fields['middle_name']['visible'] = false;
		$this->fields['third_name']['visible'] = false;
		$this->fields['preferred_name']['visible'] = false;
		$this->fields['last_name']['visible'] = false;
		$this->fields['gender_id']['visible'] = false;
		$this->fields['date_of_birth']['visible'] = false;
		$this->fields['identity']['visible'] = false;

		$this->ControllerAction->field('name');
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$options['auto_search'] = false;
		$query->find('notSuperAdmin');

		$search = $this->ControllerAction->getSearchKey();

		if (!empty($search)) {
			$query = $this->addSearchConditions($query, ['searchTerm' => $search]);
		}
	}

	public function findNotSuperAdmin(Query $query, array $options) {
		return $query->where([$this->aliasField('super_admin') => 0]);
	}

	public function viewBeforeAction(Event $event) {
		$this->ControllerAction->field('roles', [
			'type' => 'role_table', 
			'order' => 69,
			'valueClass' => 'table-full-width',
			'visible' => ['index' => false, 'view' => true, 'edit' => false]
		]);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupTabElements(['id' => $entity->id]);
	}

	private function setupTabElements($options) {
		$this->controller->set('selectedAction', 'Securities');
		$this->controller->set('tabElements', $this->controller->getUserTabElements($options));
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->find('notSuperAdmin');
	}

	public function viewBeforeQuery(Event $event, Query $query) {
		$options['auto_contain'] = false;
		$query->contain(['Roles']);
	}

	public function onGetRoleTableElement(Event $event, $action, $entity, $attr, $options=[]) {
		$tableHeaders = [__('Groups'), __('Roles')];
		$tableCells = [];
		$alias = $this->alias();
		$key = 'roles';

		$Group = TableRegistry::get('Security.SecurityGroups');
		$Group->hasOne('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'security_group_id']);

		if ($action == 'view') {
			$associated = $entity->extractOriginal([$key]);
			if (!empty($associated[$key])) {
				foreach ($associated[$key] as $i => $obj) {
					$groupId = $obj['_joinData']->security_group_id;
					$groupEntity = $Group->find()
						->where([$Group->aliasField('id') => $groupId])
						->contain('Institutions')
						->first()
						;

					$rowData = [];
					if ($groupEntity) {
						$url = [
							'plugin' => $this->controller->plugin,
							'controller' => $this->controller->name,
							'view',
							$groupEntity->id
						];
						if (!empty($groupEntity->institution)) {
							$url['action'] = 'SystemGroups';
						} else {
							$url['action'] = 'UserGroups';
						}
						$rowData[] = $event->subject()->Html->link($groupEntity->name, $url);
					} else {
						$rowData[] = '';
					}

					$rowData[] = $obj->name; // role name
					$tableCells[] = $rowData;
				}
			}
		}
		$attr['tableHeaders'] = $tableHeaders;
    	$attr['tableCells'] = $tableCells;

		return $event->subject()->renderElement('User.Accounts/' . $key, ['attr' => $attr]);
	}

	public function addBeforeAction(Event $event) {
		$uniqueOpenemisId = $this->getUniqueOpenemisId(['model'=>Inflector::singularize('User')]);
		
		// first value is for the hidden field value, the second value is for the readonly value
		$this->ControllerAction->field('openemis_no', ['type' => 'readonly', 'value' => $uniqueOpenemisId, 'attr' => ['value' => $uniqueOpenemisId]]);
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// not saving empty passwords
		if (empty($data[$this->alias()]['password'])) {
			unset($data[$this->alias()]['password']);
		}
	}

	public function validationDefault(Validator $validator) {
		$BaseUsers = TableRegistry::get('User.Users');
		return $BaseUsers->setUserValidation($validator, $this);
	}
}
