<?php
namespace Security\Model\Table;

use User\Model\Table\UsersTable as BaseTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class UsersTable extends BaseTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->entityClass('User.User');
		$this->addBehavior('Security.User');

		$this->belongsToMany('SecurityRoles', [
			'className' => 'Security.SecurityRoles',
			'foreignKey' => 'security_role_id',
			'targetForeignKey' => 'security_user_id',
			'through' => 'Security.SecurityGroupUsers',
			'dependent' => true
		]);

		$this->addBehavior('Area.Areapicker');
	}

	public function addAfterAction(Event $event) {
		if (isset($this->fields['openemis_no'])) { // to make openemis_no editable in Security -> Users
			if (isset($this->fields['openemis_no']['attr'])) {
	        	unset($this->fields['openemis_no']['attr']);
	        }
        }
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

	public function editBeforeAction(Event $event) {
		$this->ControllerAction->field('address_area_id', ['type' => 'areapicker', 'source_model' => 'Area.AreaAdministratives']);
		$this->ControllerAction->field('birthplace_area_id', ['type' => 'areapicker', 'source_model' => 'Area.AreaAdministratives']);
	}

	public function viewBeforeAction(Event $event) {
		parent::viewBeforeAction($event);
		$this->hideFieldsBasedOnRole();
	}	

	public function addEditBeforeAction(Event $event) {
		parent::addEditBeforeAction($event);
		$this->hideFieldsBasedOnRole();
	}	

	public function addBeforeAction(Event $event) {
		$uniqueOpenemisId = $this->getUniqueOpenemisId(['model'=>Inflector::singularize('User')]);
		
		//$this->ControllerAction->field('openemis_no', ['type' => 'readonly', 'attr' => ['value' => $uniqueOpenemisId]]);
		$this->fields['openemis_no']['attr']['readonly'] = true;
		$this->fields['openemis_no']['attr']['value'] = $uniqueOpenemisId;
	}

	public function hideFieldsBasedOnRole(){
		//hide Address, postal code, gender and birthdate from user account page
		$roleName = $this->controller->name;
		$this->ControllerAction->field('address', ['visible' => false]);
		$this->ControllerAction->field('postal_code', ['visible' => false]);
	}

	public function validationDefault(Validator $validator) {
		parent::validationDefault($validator);
		$validator
			->allowEmpty('address')
			->allowEmpty('postal_code')
			;
		return $validator;
	}

}
