<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class UtilityInternetTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('utility_internet_types');
        parent::initialize($config);

        $this->hasMany('InfrastructureUtilityInternets', ['className' => 'Institution.InfrastructureUtilityInternets', 'foreignKey' => 'utility_internet_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
