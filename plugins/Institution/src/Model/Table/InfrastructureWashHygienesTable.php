<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Query;

class InfrastructureWashHygienesTable extends AppTable {

    public function initialize(array $config)
    {
        $this->table('infrastructure_wash_hygienes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('InfrastructureWashHygieneTypes',   ['className' => 'Institution.InfrastructureWashHygieneTypes', 'foreign_key' => 'infrastructure_wash_hygiene_type_id']);
        $this->belongsTo('InfrastructureWashHygieneSoapashAvailabilities',   ['className' => 'Institution.InfrastructureWashHygieneSoapashAvailabilities', 'foreign_key' => 'infrastructure_wash_hygiene_use_id']);
        $this->belongsTo('InfrastructureWashHygieneEducations',   ['className' => 'Institution.InfrastructureWashHygieneEducations', 'foreign_key' => 'infrastructure_wash_hygiene_education_id']);
        // $this->hasMany('InfrastructureWashHygieneQuantities', ['className' => 'Institution.InfrastructureWashHygieneQuantities', 'foreign_key' => 'infrastructure_wash_hygiene_id', 'dependent' => true, 'cascadeCallbacks' => true]);
    }
}