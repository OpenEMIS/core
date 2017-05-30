<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;

class ViewBehavior extends Behavior
{
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.view'] = 'view';
        return $events;
    }

    public function view(Event $mainEvent, ArrayObject $extra)
    {
        $model = $this->_table;

        $event = $model->dispatchEvent('ControllerAction.Model.view.beforeAction', [$extra], $this);
        if ($event->isStopped()) {
            $mainEvent->stopPropagation();
            return $event->result;
        }
        if ($event->result instanceof Table) {
            $model = $event->result;
        }


        $sessionKey = $model->registryAlias() . '.primaryKey';
        $contain = [];

        foreach ($model->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // only contain belongsTo associations
                $contain[] = $assoc->name();
            }
        }

        $ids = empty($model->paramsPass(0)) ? [] : $model->paramsDecode($model->paramsPass(0));

        if (empty($ids)) {
            if ($model->Session->check($sessionKey)) {
                $ids = $model->Session->read($sessionKey);
            } elseif (!empty($model->ControllerAction->getQueryString())) {
                // Query string logic not implemented yet, will require to check if the query string contains the primary key
                $primaryKey = $model->primaryKey();
                $ids = $model->ControllerAction->getQueryString($primaryKey);
            }
        }

        $idKeys = $model->getIdKeys($model, $ids);

        $entity = false;

        // need to change this part
        if ($model->exists([$idKeys])) {
            $query = $model->find()->where($idKeys)->contain($contain);

            $event = $model->dispatchEvent('ControllerAction.Controller.beforeQuery', [$model, $query, $extra], $this);
            $event = $model->dispatchEvent('ControllerAction.Model.viewEdit.beforeQuery', [$query, $extra], $this);
            $event = $model->dispatchEvent('ControllerAction.Model.view.beforeQuery', [$query, $extra], $this);

            $entity = $query->first();
        }

        $event = $model->dispatchEvent('ControllerAction.Model.view.afterAction', [$entity, $extra], $this);
        if ($event->isStopped()) {
            $mainEvent->stopPropagation();
            return $event->result;
        }
        if (!empty($entity)) {
            $model->Session->write($sessionKey, $ids);
            $model->controller->set('data', $entity);
        } else {
            $mainEvent->stopPropagation();
            return $model->controller->redirect($model->url('index', 'QUERY'));
        }
        return $entity;
    }
}
