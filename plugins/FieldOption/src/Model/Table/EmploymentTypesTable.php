<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class EmploymentTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('employment_types');
        parent::initialize($config);
        $this->hasMany('Employments', ['className' => 'Staff.Employments', 'foreignKey' => 'employment_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
