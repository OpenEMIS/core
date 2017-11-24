<?php
namespace Quality\Model\Behavior;

use ArrayObject;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class VisitBehavior extends Behavior
{
    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
        return $events;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $model = $this->_table;

        $plugin = $model->controller->plugin;
        $controller = $model->controller->name;
        $action = $model->alias;

        $tabElements = [];
        if ($model->AccessControl->check(['controller' => $model->controller->name, 'action' => 'VisitRequests', 'view'])) {
            $tabElements['VisitRequests'] = [
                'url' => ['plugin' => $plugin, 'controller' => $controller, 'action' => 'VisitRequests'],
                'text' => __('Requests')
            ];
        }

        if ($model->AccessControl->check(['controller' => $model->controller->name, 'action' => 'Visits', 'view'])) {
            $tabElements['Visits'] = [
                'url' => ['plugin' => $plugin, 'controller' => $controller, 'action' => 'Visits'],
                'text' => __('Visits')
            ];
        }
        $tabElements = $model->controller->TabPermission->checkTabPermission($tabElements);
        $model->controller->set('tabElements', $tabElements);
        $model->controller->set('selectedAction', $action);
    }
}
