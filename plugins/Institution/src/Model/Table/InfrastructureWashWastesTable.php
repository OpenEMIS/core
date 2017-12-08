<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InfrastructureWashWastesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_wash_wastes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('InfrastructureWashWasteTypes',   ['className' => 'Institution.InfrastructureWashWasteTypes', 'foreign_key' => 'infrastructure_wash_waste_type_id']);
        $this->belongsTo('InfrastructureWashWasteFunctionalities',   ['className' => 'Institution.InfrastructureWashWasteFunctionalities', 'foreign_key' => 'infrastructure_wash_waste_functionality_id']);
    }
}
