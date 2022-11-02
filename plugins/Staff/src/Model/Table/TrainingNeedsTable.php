<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Staff\Model\Table\TrainingNeedsAppTable;

class TrainingNeedsTable extends TrainingNeedsAppTable
{
    public function initialize(array $config)
    {
        $this->table('staff_training_needs');
        parent::initialize($config);

        $this->addBehavior('Workflow.Workflow', ['model' => 'Institution.StaffTrainingNeeds']);
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
