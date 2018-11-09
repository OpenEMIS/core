<?php
namespace Staff\Model\Table;

use App\Model\Table\ControllerActionTable;

class StaffTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('staff_types');
        parent::initialize($config);
        
        $this->hasMany('StaffPositionProfiles', ['className' => 'Institution.StaffPositionProfiles']);
        $this->hasMany('Staff', ['className' => 'Institution.Staff']);
        $this->hasMany('StaffTransferIn', ['className' => 'Institution.StaffTransferIn', 'foreignKey' => 'new_staff_type_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffTransferOut', ['className' => 'Institution.StaffTransferOut', 'foreignKey' => 'previous_staff_type_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStaff', ['className' => 'Report.InstitutionStaff']);
        $this->hasMany('Positions', ['className' => 'Staff.Positions']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Staff' => ['index']
        ]);
    }
}
