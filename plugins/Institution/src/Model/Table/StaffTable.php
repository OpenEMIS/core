<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use User\Model\Table\UsersTable as BaseTable;

class StaffTable extends BaseTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->entityClass('User.User');
		$this->addBehavior('Staff.Staff');
		$this->addBehavior('User.Mandatory', ['userRole' => 'Staff', 'roleFields' =>['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		$this->addBehavior('Institution.User', ['associatedModel' => $this->InstitutionSiteStaff]);
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

			$list = $this->InstitutionSiteStaff
					->find('all')
					->contain(['Users'])
					->where($conditions)
					;

			$session = $this->request->session();
			if ($session->check($this->controller->name.'.'.$this->alias)) {
				$filterData = $session->read($this->controller->name.'.'.$this->alias);
				// need to form an exclude list
				$excludeQuery = $this->InstitutionSiteStaff
					->find()
					->select(['security_user_id'])
					->where(
						[
							'AND' => $filterData
						]
					)
					->group('security_user_id')
				;
				$excludeList = [];
				foreach ($excludeQuery as $key => $value) {
					$excludeList[] = $value->security_user_id;
				}
				
				if(!empty($excludeList)) {
					$list->where([$this->InstitutionSiteStaff->aliasField('security_user_id').' NOT IN' => $excludeList]);
				}
			}
			
			$list
				->group('Users.id')
				->order(['Users.first_name asc']);

			$data = array();
			foreach ($list as $obj) {
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