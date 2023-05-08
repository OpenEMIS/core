<?php
namespace Directory\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class StudentCounsellingTable extends ControllerActionTable
{
    const ASSIGNED = 1;

    public function initialize(array $config)
    {
        $this->table('institution_counsellings');
        parent::initialize($config);
        $this->toggle('add', true);
        //$this->addBehavior('Excel', ['pages' => ['index']]);
    }

    
}
