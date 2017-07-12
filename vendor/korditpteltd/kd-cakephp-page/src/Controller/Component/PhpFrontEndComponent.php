<?php
namespace Page\Controller\Component;

use ArrayObject;
use Exception;

use Cake\Core\Configure;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

use Page\Model\Entity\PageElement;
use Page\Model\Entity\PageFilter;
use Page\Model\Entity\PageStatus;

class PhpFrontEndComponent extends Component
{
    private $controller = null;

    public $components = ['Page.Page', 'Alert'];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->controller = $this->_registry->getController();
    }

    // Is called after the controller's beforeFilter method but before the controller executes the current action handler.
    public function startup(Event $event)
    {
        $request = $this->request;
        $page = $this->Page;

        if ($request->is('get')) {
            $querystring = $page->getQueryString();
            if (array_key_exists('alert', $querystring)) {
                $this->controller->set('alert', $querystring['alert']);
                $page->setQueryString('alert', null);
            }
        }
    }

    // Is called after the controller executes the requested action’s logic, but before the controller renders views and layout.
    public function beforeRender(Event $event)
    {
        $controller = $this->controller;
        $request = $this->request;
        $action = $request->action;
        $page = $this->Page;
        $elements = $page->getElements();

        foreach ($elements as $element) {
            if ($element->getKey() == 'openemis_no') {
                $element->setLabel('OpenEMIS ID');
            }

            $this->formatTime($element);
        }
        if ($request->is(['put', 'post'])) {
            $page->showElements(true);
        }

        $controller->set('menuItemSelected', [$controller->name]);

        $status = $page->getStatus();

        switch($action) {
            case 'add':
                $msg = $status->getMessage();
                if ($request->is(['post'])) {
                    switch($status->getCode()) {
                        case PageStatus::SUCCESS:
                            $page->setQueryString('alert', ['type' => $status->getType(), 'message' => $msg], true);
                            $url = $this->Page->getUrl(['action' => 'index'], 'QUERY');

                            return $this->controller->redirect($url);
                            break;
                        case PageStatus::VALIDATION_ERROR:
                        case PageStatus::UNEXPECTED_ERROR:
                            $this->controller->set('alert', ['type' => $status->getType(), 'message' => $msg]);
                            break;
                    }
                }

                break;
            case 'edit':
                $msg = $status->getMessage();
                if ($request->is(['post', 'put'])) {
                    switch($status->getCode()) {
                        case PageStatus::SUCCESS:
                            $page->setQueryString('alert', ['type' => $status->getType(), 'message' => $msg], true);
                            $url = $this->Page->getUrl(['action' => 'view']);

                            return $this->controller->redirect($url);
                            break;
                        case PageStatus::VALIDATION_ERROR:
                        case PageStatus::UNEXPECTED_ERROR:
                            $this->controller->set('alert', ['type' => $status->getType(), 'message' => $msg]);
                            break;
                    }
                }
                break;
            case 'delete':
                $isSuccessful = $request->is('delete') && $status->getCode() == PageStatus::SUCCESS;
                $isRecordNotFound = $status->getCode() == PageStatus::RECORD_NOT_FOUND;
                if ($isSuccessful || $isRecordNotFound) {
                    $msg = $status->getMessage();
                    $page->setQueryString('alert', ['type' => $status->getType(), 'message' => $msg], true);
                    $url = $this->Page->getUrl(['action' => 'index'], 'QUERY');
                    return $this->controller->redirect($url);
                }
                break;
        }

        if ($page->isAutoRender() && in_array($action, ['index', 'view', 'add', 'edit', 'delete'])) {
            $viewFile = 'Page.Page/' . $action;
            $this->controller->viewBuilder()->template($viewFile);
        }
    }

    // public function implementedEvents()
    // {
    //     $eventMap = [

    //     ];

    //     $events = parent::implementedEvents();
    //     foreach ($eventMap as $event => $method) {
    //         if (!method_exists($this, $method['callable'])) {
    //             continue;
    //         }
    //         $events[$event] = $method;
    //     }
    //     return $events;
    // }

    private function formatTime(PageElement $element)
    {
        if ($element->getControlType() == 'time') {
            $element->attr('format', 'H:i:s');
        }
    }
}
