<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;

class InstitutionEquipmentTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EquipmentTypes', ['className' => 'Institution.EquipmentTypes']);
        $this->belongsTo('EquipmentPurposes', ['className' => 'Institution.EquipmentPurposes']);
        $this->belongsTo('EquipmentConditions', ['className' => 'Institution.EquipmentConditions']);
    }
}
