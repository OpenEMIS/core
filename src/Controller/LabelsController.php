<?php
namespace App\Controller;

use Cake\Event\Event;

use Page\Controller\PageController;

class LabelsController extends PageController
{
    public function initialize()
    {
        parent::initialize();

        $this->Page->loadElementsFromTable($this->Labels);
        $this->Page->disable(['add', 'delete']);
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

        // to keep the value when there is an error occured
        if (array_key_exists('Labels', $this->request['data']) && !empty($this->request['data']['Labels']['code'])) {
            $code = $this->request['data']['Labels']['code'];
            $page->get('code')->setValue($code);
        }

        parent::edit($id);
    }
}
