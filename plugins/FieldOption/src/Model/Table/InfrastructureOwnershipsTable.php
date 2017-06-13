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

        $this->hasMany('InstitutionLands', ['className' => 'Institution.InstitutionLands', 'foreignKey' => 'infrastructure_ownership_id']);
        $this->hasMany('InstitutionBuildings', ['className' => 'Institution.InstitutionLands', 'foreignKey' => 'infrastructure_ownership_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
