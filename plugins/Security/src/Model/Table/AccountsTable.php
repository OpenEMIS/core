<?php
namespace Security\Model\Table;

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
		$this->addBehavior('User.Account', ['userRole' => 'Securities', 'permission' => ['Securities', 'Accounts', 'edit']]);
		parent::initialize($config);
	}

	public function validationDefault(Validator $validator): Validator {
		$validator = parent::validationDefault($validator);
		return $validator;
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

        // Get the list of changed properties
        $changedProperties = $entity->getDirty();

        // Extract the original values of the changed properties
        $originalValues = [];
        foreach ($changedProperties as $property) {
            $originalValues[$property] = $entity->getOriginal($property);
        }

        // Check if password is one of the changed properties
        if (isset($originalValues['password'])) {
            $setPassword = $originalValues['password'];
            // Perform your operations with $setPassword
        }


        $securityData = $userTable->find()->where([$userTable->aliasField('id')=>$entity->id])->first()->username;
        $check = strcmp($securityData, $entity->username);
        $userPasswordUpdte = $userTable->updateAll(
                        ['password' => $setPassword,
                            ],    //field
                        ['id' => $entity->id,
                        ] //condition
                    );
        if($check == 0){
            $field = 'password';
            $old = $entity->password;
            $new = $setPassword;
        }else{
            $field = 'username';
            $old = $securityData;
            $new = $entity->username;
        }
        $data = [
                    'model' => 'SecurityAccount',
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
        $message = __('Record is Updated Successfully');
        $this->Alert->success($message, ['type' => 'string', 'reset' => true]);
        $queryParams = $this->request->getParam('pass')[1];
        $url = ['plugin' => 'Security', 'controller' => 'Securities',
                 'action' => 'Accounts',0 => 'view',1 => $this->request->getParam('pass')[1]];
        return $this->controller->redirect($url);

        
            
        
    }
}
