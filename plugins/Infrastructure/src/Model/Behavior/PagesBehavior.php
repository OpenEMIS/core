<?php
namespace Infrastructure\Model\Behavior;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\Behavior;
use Cake\Event\Event;

class PagesBehavior extends Behavior
{
    private $modules = ['Land', 'Building', 'Floor', 'Room'];

    protected $_defaultConfig = [
        'module' => null
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction', 'priority' => 1];
        return $events;
    }

    public function beforeAction(Event $event)
    {
        $model = $this->_table;
        $action = $model->action;
        $url = $model->ControllerAction->url($action);

        $selectedModule = $model->request->query('module');
        if (!is_null($selectedModule)) {
            $customModule = $model->CustomModules
                ->find()
                ->where([$model->CustomModules->aliasField('id') => $selectedModule])
                ->first();

            $module = $this->config('module');
            $registryAlias = 'Institution.Institution'.$module.'s';
            $action = $module.'Pages';
            // call from infrastructure, if room selected, redirect to room
            if ($customModule->model == $registryAlias) {
                $url['action'] = 'RoomPages';
                $event->stopPropagation();
                return $model->controller->redirect($url);
            }
        }
    }

    public function getModules()
    {
        return $this->modules;
    }
}
