<?php
namespace Institution\Model\Table;

use User\Model\Table\UsersTable as BaseTable;
use Cake\Validation\Validator;

class StudentsTable extends BaseTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->entityClass('User.User');
		$this->addBehavior('Student.Student');
		$this->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' =>['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		$this->addBehavior('Institution.User', ['associatedModel' => $this->InstitutionSiteStudents]);
	}
}