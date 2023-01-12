<?php
namespace Profile\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

class AccountsTable extends AppTable
{
    private $targetField = null;

	public function initialize(array $config)
    {
		$this->addBehavior('User.Account');
		parent::initialize($config);
	}

	public function validationDefault(Validator $validator)
    {
		$validator = parent::validationDefault($validator);
		return $validator
            ->add('current_password', [
                'ruleChangePassword' => [
                    'rule' => ['checkUserPassword', $this],
                    'provider' => 'table',
                ]
            ])
        ;
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
        
        $userActivities = TableRegistry::get('user_activities');
        $userTable = TableRegistry::get('security_users');
        $user = $this->Auth->user();
        $userId = $user['id'];
        $currentTimeZone = date("Y-m-d H:i:s");
        $newpassword = $entity->extractOriginalChanged($entity->visibleProperties());
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
    }

}
