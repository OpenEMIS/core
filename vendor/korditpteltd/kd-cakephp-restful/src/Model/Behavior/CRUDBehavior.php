<?php
namespace Restful\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;

class CRUDBehavior extends Behavior {
    public function implementedEvents()
    {
        $eventMap = [];
        $eventMap['Restful.Model.onBeforeAction'] = 'onBeforeAction';

        $events = parent::implementedEvents();

        foreach ($eventMap as $event => $method) {
            if (!method_exists($this, $method)) {
                continue;
            }
            $events[$event] = $method;
        }
        return $events;
    }

    public function onBeforeAction(Event $event, $action, ArrayObject $columns, ArrayObject $extra)
    {
        $model = $this->_table;
        $model->dispatchEvent('Restful.CRUD.beforeAction', [$action, $columns, $extra], $model);
        $model->dispatchEvent('Restful.CRUD.'.lcfirst(Inflector::camelize($action)).'.beforeAction', [$columns, $extra], $model);
    }
}
