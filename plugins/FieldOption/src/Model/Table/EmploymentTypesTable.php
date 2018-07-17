<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class EmploymentTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('employment_types');
        parent::initialize($config);

        $this->addBehavior('FieldOption.FieldOption');
    }

}
