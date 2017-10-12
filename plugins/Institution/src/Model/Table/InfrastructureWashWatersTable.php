<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InfrastructureWashWatersTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_wash_waters');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('InfrastructureWashWaterTypes',   ['className' => 'Institution.InfrastructureWashWaterTypes', 'foreign_key' => 'infrastructure_wash_water_type_id']);
        $this->belongsTo('InfrastructureWashWaterFunctionalities',   ['className' => 'Institution.InfrastructureWashWaterFunctionalities', 'foreign_key' => 'infrastructure_wash_water_functionality_id']);
        $this->belongsTo('InfrastructureWashWaterProximities',   ['className' => 'Institution.InfrastructureWashWaterProximities', 'foreign_key' => 'infrastructure_wash_water_proximity_id']);
        $this->belongsTo('InfrastructureWashWaterQuantities',   ['className' => 'Institution.InfrastructureWashWaterQuantities', 'foreign_key' => 'infrastructure_wash_water_quantity_id']);
        $this->belongsTo('InfrastructureWashWaterQualities',   ['className' => 'Institution.InfrastructureWashWaterQualities', 'foreign_key' => 'infrastructure_wash_water_quality_id']);
        $this->belongsTo('InfrastructureWashWaterAccessibilities',   ['className' => 'Institution.InfrastructureWashWaterAccessibilities', 'foreign_key' => 'infrastructure_wash_water_accessibility_id']);
    }
}
