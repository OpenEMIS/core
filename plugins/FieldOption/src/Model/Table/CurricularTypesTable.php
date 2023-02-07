<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class CurricularTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('curricular_types');
        parent::initialize($config);
        
        $this->addBehavior('FieldOption.FieldOption');
    }
}
