<?php

namespace App\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Utility\Inflector;
use Cake\Http\ServerRequest;
use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;

class InfrastructureCustomFieldOptionsTable extends ControllerActionTable
{

    public function initialize(array $config): void
    {
        $this->setTable('infrastructure_custom_field_options');
        parent::initialize($config);
    }

}
