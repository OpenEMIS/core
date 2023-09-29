<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class InfrastructureConditionsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_conditions');
        parent::initialize($config);

        $this->hasMany('InstitutionLands', ['className' => 'Institution.InstitutionLands', 'foreignKey' => 'infrastructure_condition_id']);
        $this->hasMany('InstitutionBuildings', ['className' => 'Institution.InstitutionBuildings', 'foreignKey' => 'infrastructure_condition_id']);
        $this->hasMany('InstitutionFloors', ['className' => 'Institution.InstitutionFloors', 'foreignKey' => 'infrastructure_condition_id']);
        $this->hasMany('InstitutionRooms', ['className' => 'Institution.InstitutionRooms', 'foreignKey' => 'infrastructure_condition_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
