<?php
namespace App\Controller;

use Cake\Event\Event;

use App\Controller\PageController;

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
        $this->Page->addCrumb('Localization', ['plugin' => false, 'controller' => 'Locales', 'action' => 'index']);

        $this->Page->get('direction')
            ->setControlType('select')
            ->setOptions([
                '' => '-- Select --', 'ltr' => 'Left to Right', 'rtl' => 'Right to Left'
            ]);

        $this->Page->exclude(['editable']);
    }

    public function index()
    {
        $page = $this->Page;
        $page->exclude(['iso', 'full_iso']);
        parent::index();
    }
}
