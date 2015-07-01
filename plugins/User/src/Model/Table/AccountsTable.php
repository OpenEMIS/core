<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;

class AccountsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('security_users');
		parent::initialize($config);

		// todo:mlee cannot extend user table -  too much baggage
		// need to automate association adding
		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);

		$this->hasMany('InstitutionSiteStaff', ['className' => 'Institution.InstitutionSiteStaff', 'foreignKey' => 'security_user_id']);
		$this->hasMany('InstitutionSiteStudents', ['className' => 'Institution.InstitutionSiteStudents', 'foreignKey' => 'security_user_id']);
		$this->hasMany('InstitutionSiteStaff', ['className' => 'Institution.InstitutionSiteStaff', 'foreignKey' => 'security_user_id']);
		$this->hasMany('Identities', ['className' => 'User.Identities', 'foreignKey' => 'security_user_id']);
		$this->hasMany('Nationalities', ['className' => 'User.Nationalities', 'foreignKey' => 'security_user_id']);
		$this->hasMany('SpecialNeeds', ['className' => 'User.SpecialNeeds', 'foreignKey' => 'security_user_id']);
		$this->hasMany('Contacts', ['className' => 'User.Contacts', 'foreignKey' => 'security_user_id']);
	}

	private function setTabElements() {
		if ($this->controller->name == 'Institutions') return;

		if (array_key_exists('pass', $this->request->params)) {
			$id = $this->request->params['pass'][1];
		}

		$tabElements = [
			'Details' => [
				'url' => ['plugin' => Inflector::singularize($this->controller->name), 'controller' => $this->controller->name, 'action' => 'view',$id],
				'text' => __('Details')
			],
			'Accounts' => [
				'url' => ['plugin' => Inflector::singularize($this->controller->name), 'controller' => $this->controller->name, 'action' => 'Accounts','view',$id],
				'text' => __('Account')
			]
		];

		if (!in_array($this->controller->name, ['Students', 'Staff', 'Institutions'])) {
			$tabElements['Details'] = [
				'url' => ['plugin' => Inflector::singularize($this->controller->name), 'controller' => $this->controller->name, 'action' => 'Users', 'view',$id],
				'text' => __('Details')
			];
		}

		$this->controller->set('selectedAction', $this->alias);
        $this->controller->set('tabElements', $tabElements);
	}

	public function beforeAction() {
		$fieldsNeeded = ['username','password'];
		foreach ($this->fields as $key => $value) {
			if (!in_array($key, $fieldsNeeded)) {
				$this->fields[$key]['visible'] = false;
			} else {
				$this->fields[$key]['visible'] = true;
			}
		}

		$this->setTabElements();

		$this->fields['password']['type'] = 'password';
		$this->ControllerAction->setFieldOrder(['username', 'password']);

		if (strtolower($this->action) != 'index') {
			$this->Navigation->addCrumb($this->getHeader($this->action));
		}
	}

	public function editBeforeAction($event)  {
		$this->ControllerAction->addField('retype_password', []);
		$this->fields['retype_password']['type'] = 'password';

		$this->ControllerAction->setFieldOrder(['username', 'password', 'retype_password']);
	}

	public function editBeforeQuery(Event $event, Query $query, array $contain) {
		// not retrieving password so the field wil be empty. not needed anyway.
		$query->select([$this->primaryKey(), 'username']);
		return compact('query', 'contain');
	}

	// public function editAfterSaveRedirect($action) {
	// 	$id = '';
	// 	if (array_key_exists('pass', $this->request->params)) {
	// 		$id = $this->request->params['pass'][1];
	// 	}	

	// 	if ($this->controller->name == 'Securities') {
	// 		$action = ['plugin' => Inflector::singularize($this->controller->name), 'controller' => $this->controller->name, 'action' => 'Users','view',$id];
	// 	} else {
	// 		$action = ['plugin' => Inflector::singularize($this->controller->name), 'controller' => $this->controller->name, 'action' => 'view',$id];
	// 	}
	// 	return $action;
	// }

	public function validationDefault(Validator $validator) {
		return $validator
			->requirePresence('gender_id', 'create')
			->add('password' , [
				'ruleMinLength' => [
					'rule' => ['minLength', 6],
					'on' => 'update',
					'message' => 'Password must be at least 6 characters'
				]
			])
			->add('retype_password' , [
				'ruleCompare' => [
					'rule' => ['comparePasswords', 'password'],
					'on' => 'update',
					'message' => 'Both passwords do not match'
				]
			])
			;
	}
}