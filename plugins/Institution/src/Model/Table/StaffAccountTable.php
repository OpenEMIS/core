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
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;

class StaffAccountTable extends AppTable {
	public function initialize(array $config): void {
		$this->addBehavior('User.Account', ['userRole' => 'Staff', 'isInstitution' => true, 'permission' => ['Institutions', 'StaffAccount', 'edit']]);
		parent::initialize($config);
	}

	public function validationDefault(Validator $validator): Validator {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

    public function onUpdateFieldUsername(Event $event, array $attr, $action, ServerRequest $request) {
        $editStaffUsername = $this->AccessControl->check(['Institutions', 'StaffAccountUsername', 'edit']);

        if ($editStaffUsername) {
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
        
        $userActivities = TableRegistry::get('User.UserActivities');
        $userTable = TableRegistry::get('Security.Users');
        $user = $this->Auth->user();
        $userId = $user['id'];
        $currentTimeZone = date("Y-m-d H:i:s");
        $newpassword = $entity->extractOriginalChanged($entity->getVisible());
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
                    'model' => 'StaffAccount',
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
        if ($field == 'last_login') {
            return __('Last Login');
        } elseif ($field == 'roles') {
            return __('Roles');
        } elseif ($field == 'username') {
            return __('Username');
        } elseif ($field == 'middle_name') {
            return __('Middle Name');
        } elseif ($field == 'third_name') {
            return __('Third Name');
        } elseif ($field == 'last_name') {
            return __('Last Name');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
