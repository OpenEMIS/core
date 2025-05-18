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
        $this->addBehavior('Institution.InstitutionTab');
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

    //POCOR-8356
    public function afterAction(Event $event, ArrayObject $options)
    {
        $users = TableRegistry::get('Security.Users');
        $plugin = __($this->controller->getPlugin());
        $id = $this->request->getAttribute('params')['pass'][1];
        $DecodedQueryString = $this->paramsDecode($id);
        $staffId = $DecodedQueryString['staff_id'];
        $data = $users->find()->select(['first_name'=>$users->aliasField('first_name'),'middle_name'=>$users->aliasField('middle_name'),'third_name'=>$users->aliasField('third_name'),'last_name'=>$users->aliasField('last_name')])
                ->where([$users->aliasField('id') => $staffId ])->first();
        $StaffName = $data->first_name.' '.$data->middle_name.' '.$data->third_name.' '.$data->last_name;
        try {
            $this->controller->set('contentHeader', $StaffName . ' - ' . 'Account');
        } catch (RecordNotFoundException $e) {
            Log::write('error', $e->getMessage());
        }
    }

    //POCOR-8451
    public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $errors = $entity->getErrors();
        if (empty($errors)) {
            $this->Alert->success('general.edit.success', ['reset' => true]);
            $session = $this->request->getSession();
            $session->write('successAlert', 'yes');
            $action = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffAccount','view',$this->request->getParam('pass.1')];
            return $this->controller->redirect($action);
        } 
    }

    //POCOR-8451
    public function viewBeforeAction() {    
        $session = $this->request->getSession();
        if($session->read('successAlert') === 'yes' && empty($session->read('_alert'))){
            $session->delete('successAlert');
            $this->Alert->success('general.edit.success', ['reset' => true]);
        }
    }
}
