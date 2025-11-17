<?php
namespace Quality\Model\Behavior;

use ArrayObject;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Http\ServerRequest;
use Cake\Event\EventInterface;

class VisitBehavior extends Behavior
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
        return $events;
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $model = $this->_table;

        $plugin = $model->controller->getPlugin();
        $controller = $model->controller->getName();
        $action = $model->getAlias();
        $actionName = $model->controller->getRequest()->getAttribute('params')['pass'][0];
        $queryString = $model->controller->getRequest()->getAttribute('params')['pass'][1];
        $tabElements = [];
        if ($model->AccessControl->check(['controller' => $model->controller->getName(), 'action' => 'VisitRequests', 'view'])) {
            $tabElements['VisitRequests'] = [
                'url' => ['plugin' => $plugin, 'controller' => $controller, 'action' => 'VisitRequests', '0' => $actionName, '1' => $queryString],
                'text' => __('Requests')
            ];
        }

        if ($model->AccessControl->check(['controller' => $model->controller->getName(), 'action' => 'Visits', 'view'])) {
            $tabElements['Visits'] = [
                'url' => ['plugin' => $plugin, 'controller' => $controller, 'action' => 'Visits', '0' => $actionName, '1' => $queryString],
                'text' => __('Visits')
            ];
        }
        $tabElements = $model->controller->TabPermission->checkTabPermission($tabElements);
        $model->controller->set('tabElements', $tabElements);
        $action = ($action != 'InstitutionQualityVisits') ? $action : __('Visits');
        $model->controller->set('selectedAction', $action);
    }
}
