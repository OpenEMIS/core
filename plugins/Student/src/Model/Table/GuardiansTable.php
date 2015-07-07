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
		$this->addBehavior('Student.StudentGuardian', ['associatedModel' => $this->StudentGuardians]);


		// $this->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' =>['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		// $this->addBehavior('Institution.User', ['associatedModel' => $this->StudentGuardians]);
	}

	public function onBeforeDelete(Event $event, Entity $entity, ArrayObject $options, $id) {
		// $process = function() use ($id, $options) {
		// 	$entity = $this->InstitutionSiteStudents->get($id);
		// 	return $this->InstitutionSiteStudents->delete($entity, $options->getArrayCopy());
		// };
		// return $process;
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
					'GuardianUsers.openemis_no LIKE' => $search,
					'GuardianUsers.first_name LIKE' => $search,
					'GuardianUsers.middle_name LIKE' => $search,
					'GuardianUsers.third_name LIKE' => $search,
					'GuardianUsers.last_name LIKE' => $search
				)
			);

			$list = $this->StudentGuardians
					->find('all')
					->contain(['GuardianUsers'])
					->where($conditions)
					->group('GuardianUsers.id')
					->order(['GuardianUsers.first_name asc']);

					pr($list->toArray());

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
