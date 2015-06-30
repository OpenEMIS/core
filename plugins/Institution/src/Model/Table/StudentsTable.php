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

		// $session = $this->request->session();
		// if ($session->check('Institutions.id')) {
		// 	pr('yay');
		// }
		// $currentInstitution 

		$this->addBehavior('Institution.User', ['associatedModel' => $this->InstitutionSiteStudents]);

		// needs a new behavior called userInstitution or something...
		// join to 
	}

}