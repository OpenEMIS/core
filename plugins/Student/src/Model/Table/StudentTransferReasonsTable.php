<?php
namespace Student\Model\Table;

use App\Model\Table\ControllerActionTable;

class StudentTransferReasonsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('student_transfer_reasons');
        parent::initialize($config);

        $this->hasMany('TransferRequests', ['className' => 'Institution.TransferRequests', 'foreignKey' => 'student_transfer_reason_id']);
        $this->hasMany('TransferApprovals', ['className' => 'Institution.TransferApprovals', 'foreignKey' => 'student_transfer_reason_id']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index']
        ]);
    }
}
