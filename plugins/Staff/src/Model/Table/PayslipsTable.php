<?php
namespace Staff\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;

class PayslipsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        die('Testing');
        $this->table('staff_salaries');
        parent::initialize($config);

    }  

   
}
