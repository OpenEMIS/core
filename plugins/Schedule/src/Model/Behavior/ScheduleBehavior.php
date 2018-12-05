<?php
namespace Schedule\Model\Behavior;

use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\Utility\Inflector;

class ScheduleBehavior extends Behavior
{
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction', 'priority' => 100];
        return $events;
    }

    public function beforeAction(Event $event)
    {
        $model = $this->_table;
        $controller = $this->_table->controller;
        $controllerName = $controller->name;

        // Breadcrumbs
        $navigation = $model->Navigation;
        $oldTitle = $model->getHeader($model->alias());
        $newTitle = $model->getHeader(str_replace('Schedule ', '', $oldTitle));
        $navigation->substituteCrumb($oldTitle, $newTitle);

        // Header
        $session = $model->request->session();
        $institutionName = $session->read('Institution.Institutions.name');
        $postfix = $newTitle;
        $header = $institutionName . ' - ' . $postfix;
        $controller->set('contentHeader', $header);
    }
}
