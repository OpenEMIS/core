<?php
// POCOR-9257: WebhooksQueue controller for Administration/Communications
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Utility\Inflector;

class WebhooksQueueController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
    }

    public function WebhooksQueue()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'WebhooksQueue']);
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $name = $this->name; // WebhooksQueue
        $action = $this->request->getParam('action'); // WebhooksQueue
        $actionName = __(Inflector::humanize($action));
        $header = $name . ' - ' . $actionName;
        $this->Navigation->addCrumb(__($name), ['plugin' => false, 'controller' => $this->getName(), 'action' => $action]);
        $this->Navigation->addCrumb($actionName);
        $this->set('contentHeader', $header);
        $this->set('selectedAction', $action);
    }
}
