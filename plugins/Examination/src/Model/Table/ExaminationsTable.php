<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use ArrayObject;

class ExaminationsTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        $this->table('examinations');
        parent::initialize($config);
    }

    public function addEditBeforeAction(Event $event) {

    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getExamsTab();
    }
}
