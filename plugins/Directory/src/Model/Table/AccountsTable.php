<?php
namespace Directory\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use App\Model\Table\AppTable;

class AccountsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('User.Account', ['permission' => ['Directories', 'Accounts', 'edit']]);
		parent::initialize($config);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

    private function setupTabElements()
    {
        $tabElements = $this->controller->getUserTabElements();
        $session = $this->request->session();
        $guardianId = $session->read('Guardian.Guardians.id');
        $studentId = $session->read('Student.Students.id');
        $isStudent = $session->read('Directory.Directories.is_student');
        $isGuardian = $session->read('Directory.Directories.is_guardian');
        $studentToGuardian = $session->read('Directory.Directories.studentToGuardian');
        $guardianToStudent = $session->read('Directory.Directories.guardianToStudent');

        if (!empty($isGuardian) && !empty($studentId) && !empty($guardianToStudent)) {
            $tabElements = $this->controller->getUserTabElements(['id' => $studentId, 'userRole' => 'Students']);
        } elseif (!empty($isStudent) && !empty($guardianId) && !empty($studentToGuardian)) {
            $tabElements = $this->controller->getUserTabElements(['id' => $guardianId, 'userRole' => 'Guardians']);
        } else {
            $tabElements = $this->controller->getUserTabElements();
        }

        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    /**
     * POCOR-7159
     * add data in user_activities table while updating password
    */
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options) 
    {
        //echo "<pre>"; print_r($this->request);die;
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
                    'model' => 'DirectoryAccount',
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
