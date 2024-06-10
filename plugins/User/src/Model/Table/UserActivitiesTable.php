<?php

namespace User\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;

class UserActivitiesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('CreatedUser', ['className' => 'User.Users', 'foreignKey' => 'created_user_id']);
        $this->addBehavior('Activity');
        $this->addBehavior('Institution.InstitutionTab', [
                    'implementedMethods' => [
                        'setUserTabElements' => 'setUserTabElements',
                    ],
                    'appliedAction' => [
                        'UserActivities' => ['id']
                    ]
                ]);

        $this->addBehavior('User.SetupTab');
        $this->addBehavior('User.UserTab');
        $this->toggle('remove', false); // POCOR-7934
        $this->toggle('edit', false); // POCOR-7934
//        $this->toggle('remove', false); // POCOR-7934


    }
}
