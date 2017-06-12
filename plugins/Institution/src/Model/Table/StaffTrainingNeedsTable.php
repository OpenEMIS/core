<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Staff\Model\Table\TrainingNeedsAppTable;

class StaffTrainingNeedsTable extends TrainingNeedsAppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Workflow.Workflow');
    }

    public function beforeAction()
    {
        $modelAlias = 'Needs';
        $userType = 'StaffUser';
        $this->controller->changeUserHeader($this, $modelAlias, $userType);
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    private function setupTabElements()
    {
        $tabElements = $this->controller->getTrainingTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }
}
