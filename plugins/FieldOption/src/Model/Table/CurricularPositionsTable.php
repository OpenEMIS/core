<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

/**
 * POCOR-6673
 */
class CurricularPositionsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('curricular_positions');
        parent::initialize($config);
        
        $this->addBehavior('FieldOption.FieldOption');
    }
}
