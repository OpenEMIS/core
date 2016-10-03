<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class InfrastructureOwnershipsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_ownerships');
        parent::initialize($config);

        $this->hasMany('InstitutionInfrastructures', ['className' => 'Institution.InstitutionInfrastructures', 'foreignKey' => 'infrastructure_ownership_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
