<?php

namespace App\Model\Table;

use Cake\Event\Event;
use Cake\Validation\Validator;

class UserAccountsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->addBehavior('User.Account', ['userRole' => 'Preferences', 'targetField' => 'new_password', 'permission' => ['Preferences', 'UserAccounts', 'edit']]);
        parent::initialize($config);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator;
    }

    public function beforeAction(Event $event)
    {
        try {
            $tabElements = $this->controller->getUserTabElements();
        } catch (\Exception $exception) {
            die('<pre>'
                . $exception->getMessage() . "\n"
                . __FILE__ . ':' . __LINE__ . ':' . __FUNCTION__
            );
        }
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Account');
    }
}
