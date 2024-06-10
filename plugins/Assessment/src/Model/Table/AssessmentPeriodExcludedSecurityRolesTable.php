<?php

namespace Assessment\Model\Table;

use ArrayObject;
use App\Model\Traits\OptionsTrait;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\Core\Configure;
use Cake\Log\Log;
use App\Model\Table\ControllerActionTable;

class AssessmentPeriodExcludedSecurityRolesTable extends ControllerActionTable
{

    public function initialize(array $config): void
    {
        parent::initialize($config);

    }

}
