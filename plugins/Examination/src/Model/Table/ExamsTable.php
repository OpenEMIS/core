<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;

class ExamsTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        $this->table('examinations');
        parent::initialize($config);
    }

    public function addEditBeforeAction(Event $event) {

    }
}
