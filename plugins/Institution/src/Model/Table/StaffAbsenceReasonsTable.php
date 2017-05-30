<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;

class StaffAbsenceReasonsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('staff_absence_reasons');
        parent::initialize($config);

        $this->hasMany('InstitutionStaffAbsences', ['className' => 'Institution.InstitutionStaffAbsences', 'foreignKey' => 'staff_absence_reason_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
