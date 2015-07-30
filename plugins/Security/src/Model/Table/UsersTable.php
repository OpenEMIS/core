<?php
namespace Security\Model\Table;

use ArrayObject;
use User\Model\Table\UsersTable as BaseTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\Network\Request;

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

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->find('notSuperAdmin');
	}

	public function editBeforeAction(Event $event) {
		$this->ControllerAction->field('address_area_id', ['type' => 'areapicker', 'source_model' => 'Area.AreaAdministratives']);
		$this->ControllerAction->field('birthplace_area_id', ['type' => 'areapicker', 'source_model' => 'Area.AreaAdministratives']);
	}

	public function viewBeforeAction(Event $event) {
		$this->hideFieldsBasedOnRole();
	}

	public function findNotSuperAdmin(Query $query, array $options) {
		return $query->where([$this->aliasField('super_admin') => 0]);
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->find('notSuperAdmin');
	}

	public function addEditBeforeAction(Event $event) {
		$this->hideFieldsBasedOnRole();
	}	

	public function hideFieldsBasedOnRole(){
		//hide Address, postal code, gender and birthdate from user account page
		$roleName = $this->controller->name;
		$this->ControllerAction->field('address', ['visible' => false]);
		$this->ControllerAction->field('postal_code', ['visible' => false]);
		$this->ControllerAction->field('gender_id', ['visible' => false]);
		$this->ControllerAction->field('date_of_birth', ['visible' => false]);
	}
}
