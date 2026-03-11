<?php
// POCOR-9257: WebhookLogs controller for Administration/Communications
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Utility\Inflector;

class WebhookLogsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
    }

    public function WebhookLogs()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'WebhookLogs']);
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $name = $this->name; // WebhookLogs
        $action = $this->request->getParam('action'); // WebhookLogs
        $actionName = __(Inflector::humanize($action));
        $header = __('Webhooks') . ' - ' . __('Log');
        $this->Navigation->addCrumb(__('Webhooks'), ['plugin' => false, 'controller' => $this->getName(), 'action' => $action]);
        $this->Navigation->addCrumb(__('Log'));
        $this->set('contentHeader', $header);
        $this->set('selectedAction', $action);
    }
}
