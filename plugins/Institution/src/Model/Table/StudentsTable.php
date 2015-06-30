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
	public function autoCompleteUserList() {
		if ($this->request->is('ajax')) {
			$this->layout = 'ajax';
			$this->autoRender = false;
			$this->ControllerAction->autoRender = false;
			$term = $this->ControllerAction->request->query('term');
			$search = "";
			if(isset($term)){
				$search = '%'.$term.'%';
			}

			$conditions = array(
				'OR' => array(
					'Users.openemis_no LIKE' => $search,
					'Users.first_name LIKE' => $search,
					'Users.middle_name LIKE' => $search,
					'Users.third_name LIKE' => $search,
					'Users.last_name LIKE' => $search
				)
			);

			//$options = array('id', 'Users.first_name', 'Users.last_name', 'Users.middle_name', 'Users.third_name', 'Users.openemis_no', 'Users.date_of_birth');
			//$Students = TableRegistry::get('Institution.InstitutionSiteStudents');
			$list = $this->InstitutionSiteStudents
					->find('all')
					//->select($options)
					->contain(['Users'])
					->where($conditions)
					->order(['Users.first_name asc']);

			$data = array();
			foreach ($list as $obj) {

				//pr($obj->user);

				$data[] = array(
					'label' => $obj->user->nameWithId,
					'value' =>  $obj->user->id
				);
			}
			
			echo json_encode($data);
			die;
		}
	}
}