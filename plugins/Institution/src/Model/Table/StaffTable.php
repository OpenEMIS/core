<?php
namespace Institution\Model\Table;

use User\Model\Table\UsersTable as BaseTable;
use Cake\Validation\Validator;

class StaffTable extends BaseTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->entityClass('User.User');
		$this->addBehavior('Staff.Staff');
		$this->addBehavior('User.Mandatory', ['userRole' => 'Staff', 'roleFields' =>['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		$this->addBehavior('Institution.User', ['associatedModel' => $this->InstitutionSiteStaff]);
	}
}