<?php
namespace App\Controller;

use App\Controller\PageController;
use Cake\Event\Event;

class NoticesController extends PageController
{
    public function initialize()
    {
        parent::initialize();

        $this->Page->loadElementsFromTable($this->Notices);
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Page->addCrumb('Notices', ['plugin' => false, 'controller' => 'Notices', 'action' => 'index']);
    }

    public function index()
    {
        $page = $this->Page;
        parent::index();

        // created_on
        $page->addNew('created_on');
        $page->get('created_on')->setDisplayFrom('created');
        $page->move('created_on')->first();
    }
}