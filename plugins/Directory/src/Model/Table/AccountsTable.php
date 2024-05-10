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
	public function initialize(array $config): void {
		$this->addBehavior('User.Account', ['permission' => ['Directories', 'Accounts', 'edit']]);
		parent::initialize($config);
	}

	public function validationDefault(Validator $validator): Validator {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

    private function setupTabElements()
    {
        $tabElements = $this->controller->getUserTabElements();
        $session = $this->request->getSession();
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
        $this->controller->set('selectedAction', $this->getAlias());
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
        $userActivities = TableRegistry::get('User.UserActivities');
        $userTable = TableRegistry::get('User.Users');
        $user = $this->Auth->user();
        $userId = $user['id'];
        $currentTimeZone = date("Y-m-d H:i:s");
        //$newpassword = $entity->extractOriginalChanged($entity->visibleProperties());
        $setPassword =  $entity->password;
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

     public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'username') {
            return __('Username');
        } elseif ($field == 'last_login') {
            return __('Last Login');
        } elseif ($field == 'roles') {
            return __('Roles');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options) {

        $message = __('Your password has been reset successfully.');
        $this->Alert->success($message, ['type' => 'string', 'reset' => true]);
        return $this->controller->redirect(['plugin' => 'Directory', 'controller' => 'Directories', 'action' => $this->getAlias(),'view',$this->request->getParam('pass')[1]]);
    }

}
