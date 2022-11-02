<?php
namespace MoodleApi\Controller;

use Cake\Event\Event;
use App\Controller\PageController;


class MoodleApiLogController extends PageController
{

    public function initialize()
    {
        parent::initialize();
        $this->Page->disable(['add', 'edit', 'delete']);
        $this->Page->loadElementsFromTable($this->MoodleApiLog);
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $page = $this->Page;

        // set Breadcrumb
        $page->addCrumb('Moodle Api Log', ['plugin' => 'MoodleApi', 'controller' => 'MoodleApiLog', 'log']);

        $header = $page->getHeader();
        $page->setHeader($header);
    }

    public function index()
    {
        $page = $this->Page;
        $page->exclude(['callback', 'callback_param']);

        $statusesOptions = $this->MoodleApiLog->getStatuses();
        $page->get('status')
            ->setControlType('select')
            ->setOptions($statusesOptions);

        parent::index();
    }

    public function view($id)
    {
        $page = $this->Page;
        parent::view($id);
    }

}
