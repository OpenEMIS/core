<?php
namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Utility\Inflector;

class MoodleApiController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Paginator');
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $name = $this->name;
        $action  = $this->request->getParam('action');
        $actionName = __(Inflector::humanize($action));
        $header = $name .' - '.$actionName;
        $this->Navigation->addCrumb(__($name), ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => $action]);
        $this->Navigation->addCrumb($actionName);
        $this->set('contentHeader', $header);
        $this->set('selectedAction', $this->request->action);
    }

    public function MoodleApi()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'MoodleApi.MoodleApiLogData']);
    }
}
