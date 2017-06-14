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

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'Needs';
        $userType = 'StaffUser';
        $this->controller->changeUserHeader($this, $modelAlias, $userType);

        // redirect to staff index page if session not found
        $session = $this->request->session();
        $sessionKey = 'Staff.Staff.id';

        if (!$session->check($sessionKey)) {
            $url = $this->url('index');
            $url['plugin'] = 'Institution';
            $url['controller'] = 'Institutions';
            $url['action'] = 'Staff';

            $event->stopPropagation();
            $this->Alert->warning('general.notExists');
            return $this->controller->redirect($url);
        }
        // End
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
