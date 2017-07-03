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
        $page = $this->Page;
        $page->get('direction')->setControlType('dropdown')->setOptions([
                '0' => '-- Select --', '1' => 'Left to Right', '2' => 'Right to Left'
                ]);
        parent::edit($id);
    }

    public function view($id)
    {

        parent::view($id);
    }

    public function add()
    {
        $page = $this->Page;
        $page->exclude(['editable']);

        $page->get('direction')->setControlType('dropdown')->setOptions([
                '0' => '-- Select --', '1' => 'Left to Right', '2' => 'Right to Left'
                    ]);

        // $page->addFilter('direction')
        //     ->setOptions([
        //         '0' => '-- Select --', '1' => 'Left to Right', '2' => 'Right to Left'
        //             ])
        //     ;

        parent::add();
    }

}
