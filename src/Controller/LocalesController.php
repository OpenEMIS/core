<?php
namespace App\Controller;

use ArrayObject;
use Cake\Event\Event;

use Page\Controller\PageController;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;

class LocalesController extends PageController
{
    public function initialize()
    {
        parent::initialize();
        $this->Page->loadElementsFromTable($this->Locales);
        $this->Page->disable(['delete']);
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Navigation->addCrumb('Localization', ['plugin' => false, 'controller' => 'LocaleContents', 'action' => 'index']);

    }


    public function index()
    {
        $page = $this->Page;
        $page->exclude(['iso', 'full_iso', 'editable']);
        parent::index();
    }

    public function edit($id)
    {
        parent::edit($id);
    }

    public function view($id)
    {

        parent::view($id);
    }

    public function add()
    {
        parent::add();
    }

}
