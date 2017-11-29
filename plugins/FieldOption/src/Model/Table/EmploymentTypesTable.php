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
        $this->hasMany('EmploymentStatuses', ['className' => 'Staff.EmploymentStatuses', 'foreignKey' => 'status_type_id']);
        $this->addBehavior('FieldOption.FieldOption');
    }
}
