<?php
namespace Gpa\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;

/**
 * POCOR-8222
 * Develop GPA features in system
 * */
class CumulativeGpaGradesTable extends ControllerActionTable {
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    


    
}
