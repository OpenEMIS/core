<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Query;

class InfrastructureWashSanitationsTable extends AppTable {

    public function initialize(array $config)
    {
        $this->table('infrastructure_wash_sanitations');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('InfrastructureWashSanitationTypes',   ['className' => 'Institution.InfrastructureWashSanitationTypes', 'foreign_key' => 'infrastructure_wash_sanitation_type_id']);
        $this->belongsTo('InfrastructureWashSanitationUses',   ['className' => 'Institution.InfrastructureWashSanitationUses', 'foreign_key' => 'infrastructure_wash_sanitation_use_id']);
        $this->belongsTo('InfrastructureWashSanitationQualities',   ['className' => 'Institution.InfrastructureWashSanitationQualities', 'foreign_key' => 'infrastructure_wash_sanitation_quality_id']);
        $this->belongsTo('InfrastructureWashSanitationAccessibilities',   ['className' => 'Institution.InfrastructureWashSanitationAccessibilities', 'foreign_key' => 'infrastructure_wash_sanitation_accessibility_id']);
        // $this->hasMany('InfrastructureWashSanitationQuantities', ['className' => 'Institution.InfrastructureWashSanitationQuantities', 'foreign_key' => 'infrastructure_wash_sanitation_id', 'dependent' => true, 'cascadeCallbacks' => true]);
    }

    // public function findView(Query $query, array $options)
    // {
    //     $query->contain(['InfrastructureWashSanitationQuantities']);
    //     return $query;
    // }

    // public function findEdit(Query $query, array $options)
    // {
    //     $query->contain(['InfrastructureWashSanitationQuantities']);
    //     return $query;
    // }
}