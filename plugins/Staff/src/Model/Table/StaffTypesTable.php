<?php
namespace Staff\Model\Table;

use App\Model\Table\ControllerActionTable;

class StaffTypesTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        $this->addBehavior('ControllerAction.FieldOption');
        $this->table('staff_types');
        parent::initialize($config);

        $this->hasMany('StaffAttendances', ['className' => 'Institution.StaffAttendances']);
        $this->hasMany('StaffPositionProfiles', ['className' => 'Institution.StaffPositionProfiles']);
        $this->hasMany('Staff', ['className' => 'Institution.Staff']);
        $this->hasMany('StaffTransfer', ['className' => 'Institution.StaffTransfer']);
        $this->hasMany('InstitutionStaffOnLeave', ['className' => 'Report.InstitutionStaffOnLeave']);
        $this->hasMany('InstitutionStaff', ['className' => 'Report.InstitutionStaff']);
        $this->hasMany('Positions', ['className' => 'Staff.Positions']);

        $this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
    }
}
