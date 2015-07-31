<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use User\Model\Table\UsersTable as BaseTable;

class GuardiansTable extends BaseTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->entityClass('User.User');
		$this->addBehavior('Guardian.Guardian');
		$this->addBehavior('Student.GuardianStudent', ['associatedModel' => $this->GuardianStudents]);
		$this->addBehavior('AdvanceSearch');
	}

	public function autoCompleteUserList() {
		if ($this->request->is('ajax')) {
			$this->layout = 'ajax';
			$this->autoRender = false;
			$this->ControllerAction->autoRender = false;
			$term = $this->ControllerAction->request->query('term');
			$search = $term;
			$searchParams = explode(' ', $search);

			$list = $this->StudentGuardians
					->find('all')
					->contain(['Users'])
					;

			$searchParams = explode(' ', $search);
			foreach ($searchParams as $key => $value) {
				if (empty($searchParams[$key])) {
					unset($searchParams[$key]);
				}
			}

			if (!empty($search)) {
				$list->where(['Users.openemis_no LIKE' => '%' . trim($search) . '%']);
				foreach ($searchParams as $key => $value) {
					$searchString = '%' . $value . '%';
					$list->orWhere(['Users.first_name LIKE' => $searchString]);
					$list->orWhere(['Users.middle_name LIKE' => $searchString]);
					$list->orWhere(['Users.third_name LIKE' => $searchString]);
					$list->orWhere(['Users.last_name LIKE' => $searchString]);
				}
			}

			$session = $this->request->session();

			if ($session->check('Students.security_user_id')) {
				$student_user_id = $session->read('Students.security_user_id');
				// need to form an exclude list
				$excludeQuery = $this->StudentGuardians
					->find()
					->select(['guardian_user_id'])
					->where(
						['student_user_id' => $student_user_id]
					)
					->group('guardian_user_id')
					;

				$excludeList = [];
				foreach ($excludeQuery as $key => $value) {
					$excludeList[] = $value->guardian_user_id;
				}

				if(!empty($excludeList)) {
					$list->where([$this->StudentGuardians->aliasField('guardian_user_id').' NOT IN' => $excludeList]);
				}
			}
			
			$list
				->group('GuardianUsers.id')
				->order(['GuardianUsers.first_name asc']);

			$data = array();

			foreach ($list as $obj) {
				$data[] = array(
					'label' => $obj->guardian_user->nameWithId,
					'value' =>  $obj->guardian_user->id
				);
			}
			
			echo json_encode($data);
			die;
		}
	}	
}
