<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class UtilityInternetConditionsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('utility_internet_conditions');
        parent::initialize($config);

        $this->hasMany('InfrastructureUtilityInternets', ['className' => 'Institution.InfrastructureUtilityInternets', 'foreignKey' => 'utility_internet_condition_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
