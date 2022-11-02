<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class InfrastructureProjectFundingSourcesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_project_funding_sources');
        parent::initialize($config);

        $this->hasMany('InfrastructureProjects', ['className' => 'Institution.InfrastructureProjects', 'foreignKey' => 'infrastructure_project_funding_source_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
