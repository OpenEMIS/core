<?php
namespace Schedule\Controller;

use App\Controller\PageController;
use Cake\Event\Event;
use Cake\ORM\Entity;

class SchedulesController extends PageController
{
    const SCHEDULED = 1;
    const RUNNING = 2;
    const STOPPED = 3;

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Schedule.ScheduleJobs');
        $this->Page->loadElementsFromTable($this->ScheduleJobs);
        $this->Page->disable(['add', 'delete']);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.Page.onRenderStatus'] = 'onRenderStatus';
        return $events;
    }

    public function edit($id)
    {
        $page = $this->Page;
        $page->get('pid')->setControlType('hidden')->setValue(null);
        $page->get('status')->setControlType('hidden')->setValue(self::STOPPED);
        $page->addNew('start_shell')->setControlType('hidden')->setValue(true);
        parent::edit($id);
    }

    public function onRenderStatus(Event $event, Entity $entity, $key)
    {
        if ($this->request->param('action') != 'edit') {
            $status = $entity->status;
            switch ($status) {
                case self::SCHEDULED:
                    return __('Scheduled');
                    break;
                case self::RUNNING:
                    return __('Running');
                    break;
                case self::STOPPED:
                    return __('Stopped');
                    break;
            }
        }
    }
}
