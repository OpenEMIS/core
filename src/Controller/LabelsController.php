<?php
namespace App\Controller;

use Cake\Event\Event;

use App\Controller\PageController;

class LabelsController extends PageController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Page.Page');
        $this->Page->loadElementsFromTable($this->Labels);
        $this->Page->setDisable(['add', 'delete']);
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;

        parent::beforeFilter($event);
        $page->addCrumb('Labels', ['plugin' => false, 'controller' => 'Labels', 'action' => 'index']);

        $page->exclude(['module', 'field', 'visible']);
    }

    public function edit($id)
    {
        $page = $this->Page;
        $page->get('module_name')->setDisabled(true);
        $page->get('field_name')->setDisabled(true);

        parent::edit($id);
    }
}
