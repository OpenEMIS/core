<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use ArrayObject;

class CentresTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        $this->table('examination_centres');
        parent::initialize($config);
    }

    public function addEditBeforeAction(Event $event) {

    }

    public function afterAction(Event $event, ArrayObject $extra)
    {

    }
}
