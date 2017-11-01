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
        $this->hasMany('InstitutionStaffTransfer', ['className' => 'Institution.InstitutionStaffTransfer']);
        $this->hasMany('InstitutionStaff', ['className' => 'Report.InstitutionStaff']);
        $this->hasMany('Positions', ['className' => 'Staff.Positions']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Staff' => ['index']
        ]);
    }
}
