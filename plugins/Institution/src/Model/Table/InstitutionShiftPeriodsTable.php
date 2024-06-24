<?php

namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Core\Configure;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

use Institution\Model\Table\Institutions;

class InstitutionShiftPeriodsTable extends ControllerActionTable
{
    use MessagesTrait;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        
    }

    
}
