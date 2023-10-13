<?php
namespace Manual\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\ORM\ResultSet;

class ManualsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('manuals');
        parent::initialize($config);
        //$this->removeBehavior('Reorder');
    } 

}
