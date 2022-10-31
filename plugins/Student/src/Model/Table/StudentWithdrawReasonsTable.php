<?php
namespace Student\Model\Table;

use App\Model\Table\ControllerActionTable;

class StudentWithdrawReasonsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('student_withdraw_reasons');
        parent::initialize($config);

        $this->hasMany('WithdrawRequests', ['className' => 'Institution.WithdrawRequests', 'foreignKey' => 'student_withdraw_reason_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentWithdraw', ['className' => 'Institution.StudentWithdraw', 'foreignKey' => 'student_withdraw_reason_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
