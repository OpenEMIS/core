<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InfrastructureWashSewagesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_wash_sewages');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('InfrastructureWashSewageTypes',   ['className' => 'Institution.InfrastructureWashSewageTypes', 'foreign_key' => 'infrastructure_wash_sewage_type_id']);
        $this->belongsTo('InfrastructureWashSewageFunctionalities',   ['className' => 'Institution.InfrastructureWashSewageFunctionalities', 'foreign_key' => 'infrastructure_wash_sewage_functionality_id']);
    }
}
