<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class InfrastructureWashSanitationQualitiesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_wash_sanitation_qualities');
        parent::initialize($config);

        $this->hasMany('InfrastructureWashSanitations', ['className' => 'Institution.InfrastructureWashSanitations', 'foreignKey' => 'infrastructure_wash_sanitation_quality_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}