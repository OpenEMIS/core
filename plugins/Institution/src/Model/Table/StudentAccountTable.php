<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class StudentAccountTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('User.Account', ['userRole' => 'Students', 'isInstitution' => true, 'permission' => ['Institutions', 'StudentAccount', 'edit']]);
		parent::initialize($config);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'view') {
				$institutionId = $this->Session->read('Institution.Institutions.id');
				$id = $this->request->query('id') ? $this->request->query('id') : $this->Session->read('Institution.Students.id');
				$StudentTable = TableRegistry::get('Institution.Students');
				$studentId = $StudentTable->get($id)->student_id;
				// Start PHPOE-1897
				if (! $StudentTable->checkEnrolledInInstitution($studentId, $institutionId)) {
					if (isset($toolbarButtons['edit'])) {
						unset($toolbarButtons['edit']);
					}
				}
				// End PHPOE-1897
			}
	}

	public function onUpdateFieldUsername(Event $event, array $attr, $action, Request $request) {
        $editStudentUsername = $this->AccessControl->check(['Institutions', 'StudentAccountUsername', 'edit']);

        if ($editStudentUsername) {
            $attr['type'] = 'string';
            return $attr;
        }
    }

    /**
     * POCOR-7159
     * add data in user_activities table while updating password
    */
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options) 
    {
        
        $userActivities = TableRegistry::get('user_activities');
        $userTable = TableRegistry::get('security_users');
        $user = $this->Auth->user();
        $userId = $user['id'];
        $currentTimeZone = date("Y-m-d H:i:s");
        $newpassword = $entity->extractOriginalChanged($entity->visibleProperties());
        $setPassword =  $newpassword['password'];

        $securityData = $userTable->find()->where([$userTable->aliasField('id')=>$entity->id])->first()->username;
        $check = strcmp($securityData, $entity->username);
        if($check==0){
            $field = 'password';
            $old = $entity->password;
            $new = $setPassword;
        }else{
            $field = 'username';
            $old = $securityData;
            $new = $entity->username;
        }
        $data = [
                    'model' => 'StudentAccount',
                    'model_reference' => $entity->id,
                    'field' => $field,
                    'field_type' => 'string',
                    'old_value' => $old,
                    'new_value' => $new,
                    'operation' => 'edit',
                    'security_user_id' => $entity->id,
                    'created_user_id' => $userId,
                    'created' => $currentTimeZone,
                ];
        $entity = $userActivities->newEntity($data);
        $save =  $userActivities->save($entity);
    }
}
