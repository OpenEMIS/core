<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use ArrayObject;

class ExaminationGradingTypesTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        parent::initialize($config);

    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getExamsTab();
    }
}
