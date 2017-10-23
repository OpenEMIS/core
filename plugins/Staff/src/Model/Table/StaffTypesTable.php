<?php
namespace Staff\Model\Table;

use App\Model\Table\ControllerActionTable;

class StaffTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('staff_types');
        parent::initialize($config);

        $this->hasMany('StaffAttendances', ['className' => 'Institution.StaffAttendances']);
        $this->hasMany('StaffPositionProfiles', ['className' => 'Institution.StaffPositionProfiles']);
        $this->hasMany('Staff', ['className' => 'Institution.Staff']);
        //institution_staff_assignments, being used on the staffTransfer(base class), which being used by staffTransferApprovalsTAble.php
        $this->hasMany('InstitutionStaffTransfer', ['className' => 'Institution.InstitutionStaffTransfer']);
        $this->hasMany('InstitutionStaff', ['className' => 'Report.InstitutionStaff']);
        $this->hasMany('Positions', ['className' => 'Staff.Positions']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Staff' => ['index']
        ]);
    }
}
