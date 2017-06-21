<?php
namespace Profile\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Validation\Validator;

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
        $this->ControllerAction->field('username', ['type' => 'readonly']);
        $this->ControllerAction->field('current_password', ['type' => 'password']);
        $this->ControllerAction->setFieldOrder(['username', 'current_password', 'password', 'retype_password']);
    }
}
