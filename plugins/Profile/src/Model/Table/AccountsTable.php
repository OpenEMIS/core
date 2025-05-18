<?php
namespace Profile\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Http\ServerRequest;
use Cake\ORM\Locator\TableLocator;
class AccountsTable extends AppTable
{
    private $targetField = null;

	public function initialize(array $config): void
    {
        
        $this->setTable('security_users');
		parent::initialize($config);
        $this->addBehavior('User.Account');
        $this->addBehavior('User.UserTab');
	}

	public function validationDefault(Validator $validator): Validator
    {

		$validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
		return $validator
            ->add('current_password', [
                'ruleChangePassword' => [
                    'rule' => ['checkUserPassword', $this],
                    'provider' => 'table',
                ]
            ]);
	}

    public function editAfterAction(Event $event, Entity $entity)
    {
        $this->ControllerAction->field('username');
        $this->ControllerAction->field('current_password', ['type' => 'password']);
        $this->ControllerAction->setFieldOrder(['username', 'current_password', 'password', 'retype_password']);
    }

    /**
     * POCOR-7159
     * add data in user_activities table while updating password
    */
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options) 
    {
        $userActivities = TableRegistry::get('User.UserActivities');
        $tableLocator = new TableLocator();
        $userTable = $tableLocator->get('security_users');
        //$userTable = TableRegistry::get('security_users');
        $user = $this->Auth->user();
        $userId = $user['id'];
        $currentTimeZone = date("Y-m-d H:i:s");

        $newpassword = $entity->extractOriginalChanged($entity->getVisible());
        $setPassword =  $newpassword['password'];

        $securityData = $userTable->find()->where([$userTable->aliasField('id')=>$userId])->first()->username;
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
                    'model' => 'Account',
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
        //POCOR-8127 starts
        if($save){
            $session = $this->request->getSession();
            //write session for API use
            $username = $this->request->getData('Accounts')['username'];
            $password = $this->request->getData('Accounts')['password'];
            $session->write('auth_username', $username);
            $session->write('auth_password', base64_encode($password));        
        }//POCOR-8127 ends
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'username') {
            return __('Username');
        }else if ($field == 'last_login') {
            return __('Last Login');
        }else if ($field == 'roles') {
            return __('Roles');
        }else if ($field == 'current_password') {
            return __('Current Password');
        }
        else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    //For POCOR-8448, POCOR-8449 PHP version 8
    public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {   
        //POCOR-8844
        $errors = $entity->getErrors();
        $param = $this->request->getParam('pass')[1];
        if (empty($errors)) {
            $this->Alert->success('general.edit.success', ['reset' => true]);
            $session = $this->request->getSession();
            $session->write('successAlert', 'yes');
           $action = ['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => 'Accounts','view',$this->request->getParam('pass.1')];
            return $this->controller->redirect($action);
        }
    }
    //POCOR-8844
    public function viewBeforeAction() {    
        $session = $this->request->getSession();
        if($session->read('successAlert') === 'yes' && empty($session->read('_alert'))){
            $session->delete('successAlert');
            $this->Alert->success('general.edit.success', ['reset' => true]);
        }
    }

}
