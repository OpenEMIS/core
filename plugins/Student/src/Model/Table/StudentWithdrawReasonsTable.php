<?php

namespace Student\Model\Table;

use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use ArrayObject;
use App\Model\Table\ControllerActionTable;

class StudentWithdrawReasonsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('student_withdraw_reasons');
        parent::initialize($config);

        $this->hasMany('WithdrawRequests', ['className' => 'Institution.WithdrawRequests', 'foreignKey' => 'student_withdraw_reason_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentWithdraw', ['className' => 'Institution.StudentWithdraw', 'foreignKey' => 'student_withdraw_reason_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('FieldOption.FieldOption');
    }
    //POCOR-9117 start
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }
    //POCOR-9117 end
}
