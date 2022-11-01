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
        $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction', 'priority' => 10];
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $model = $this->_table;

        if ($model->action == 'index') {
            $selectedModule = !is_null($model->request->query('module')) ? $model->request->query('module') : '-1';
            $CustomModules = TableRegistry::get('CustomField.CustomModules');
            $moduleDetails = $CustomModules->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code'
                ])
                ->toArray();
            $ControllerActionComponent = $event->subject();
            $request = $ControllerActionComponent->request;
            $redirectAction = isset($moduleDetails[$selectedModule]) ? ucfirst(strtolower($moduleDetails[$selectedModule])).'Pages' : null;
            if ($redirectAction && ucfirst(strtolower($moduleDetails[$selectedModule])) != $this->config('module')) {
                // call from general, if room selected, redirect to room types
                $code = $moduleDetails[$selectedModule];
                $url = $model->url('index');
                $url['action'] = $redirectAction;
                $url['module'] = $selectedModule;

                $event->stopPropagation();
                return $model->controller->redirect($url);
            }
        } else {
            unset($extra['elements']['controls']);
        }
    }

    public function getModules()
    {
        return $this->modules;
    }
}
