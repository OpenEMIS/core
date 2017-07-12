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

        if ($page->isAutoRender() && in_array($action, ['index', 'view', 'add', 'edit', 'delete'])) {
            $viewFile = 'Page.Page/' . $action;
            $this->controller->viewBuilder()->template($viewFile);
        }
    }

    public function implementedEvents()
    {
        $eventMap = [
            'Controller.Page.addAfterSave' => ['callable' => 'addAfterSave', 'priority' => 5],
            'Controller.Page.editAfterSave' => ['callable' => 'editAfterSave', 'priority' => 5]
        ];

        $events = parent::implementedEvents();
        foreach ($eventMap as $event => $method) {
            if (!method_exists($this, $method['callable'])) {
                continue;
            }
            $events[$event] = $method;
        }
        return $events;
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $extra)
    {
        $errors = $entity->errors();
        if (empty($errors)) {
            $this->Alert->success('general.add.success');
            $url = $this->Page->getUrl(['action' => 'index']);
            return $this->controller->redirect($url);
        } else {
            $this->Alert->error('general.add.failed');
        }
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $extra)
    {
        $errors = $entity->errors();
        if (empty($errors)) {
            $this->Alert->success('general.edit.success');
            $url = $this->Page->getUrl(['action' => 'view']);
            return $this->controller->redirect($url);
        } else {
            $this->Alert->error('general.edit.failed');
        }
    }

    private function formatTime(PageElement $element)
    {
        if ($element->getControlType() == 'time') {
            $element->attr('format', 'H:i:s');
        }
    }
}
