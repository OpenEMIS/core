<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class CalendarEventsTable extends ControllerActionTable {
    use OptionsTrait;
    public function initialize(array $config): void {
        $this->setTable('calendar_events');
        parent::initialize($config);
    }
}
