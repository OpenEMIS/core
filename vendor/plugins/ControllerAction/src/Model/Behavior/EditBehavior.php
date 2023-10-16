<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Log\Log;

use ControllerAction\Model\Traits\EventTrait;

class EditBehavior extends Behavior
{
    use EventTrait;

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.edit'] = 'edit';
        return $events;
    }

    public function edit(Event $mainEvent, ArrayObject $extra)
    {
        $model = $this->_table;
        $request = $model->request;
        $extra['config']['form'] = true;
        $extra['patchEntity'] = true;

        $event = $model->dispatchEvent('ControllerAction.Model.addEdit.beforeAction', [$extra], $this);
        if ($event->isStopped()) {
            return $event->result;
        }
        if ($event->result instanceof Table) {
            $model = $event->result;
        }

        $event = $model->dispatchEvent('ControllerAction.Model.edit.beforeAction', [$extra], $this);
        if ($event->isStopped()) {
            return $event->result;
        }
        if ($event->result instanceof Table) {
            $model = $event->result;
        }

        $ids = empty($model->paramsPass(0)) ? [] : $model->paramsDecode($model->paramsPass(0));
        $sessionKey = $model->registryAlias() . '.primaryKey';

        if (empty($ids)) {
            if ($model->Session->check($sessionKey)) {
                $ids = $model->Session->read($sessionKey);
            } else if (!empty($model->ControllerAction->getQueryString())) {
                // Query string logic not implemented yet, will require to check if the query string contains the primary key
                $primaryKey = $model->primaryKey();
                $ids = $model->ControllerAction->getQueryString($primaryKey);
            }
        }

        $idKeys = $model->getIdKeys($model, $ids);

        $entity = false;

        if ($model->exists($idKeys)) {
            $query = $model->find()->where($idKeys);

            $event = $model->dispatchEvent('ControllerAction.Controller.beforeQuery', [$model, $query, $extra], $this);
            $event = $model->dispatchEvent('ControllerAction.Model.viewEdit.beforeQuery', [$query, $extra], $this);
            $event = $model->dispatchEvent('ControllerAction.Model.edit.beforeQuery', [$query, $extra], $this);

            $entity = $query->first();
        }

        $event = $model->dispatchEvent('ControllerAction.Model.viewEdit.afterQuery', [$entity, $extra], $this);
        if ($event->isStopped()) {
            return $event->result;
        }

        $event = $model->dispatchEvent('ControllerAction.Model.edit.afterQuery', [$entity, $extra], $this);
        if ($event->isStopped()) {
            return $event->result;
        }

        if ($entity) {
            if ($request->is(['get'])) {
                $event = $model->dispatchEvent('ControllerAction.Model.edit.onInitialize', [$entity, $extra], $this);
                if ($event->isStopped()) {
                    return $event->result;
                }
            } else if ($request->is(['post', 'put'])) {
                $submit = isset($request->data['submit']) ? $request->data['submit'] : 'save';
                $patchOptions = new ArrayObject([]);
                $requestData = new ArrayObject($request->data);

                $params = [$entity, $requestData, $patchOptions, $extra];

                if ($submit == 'save') {
                    $event = $model->dispatchEvent('ControllerAction.Model.addEdit.beforePatch', $params, $this);
                    if ($event->isStopped()) {
                        return $event->result;
                    }

                    $event = $model->dispatchEvent('ControllerAction.Model.edit.beforePatch', $params, $this);
                    if ($event->isStopped()) {
                        return $event->result;
                    }

                    $patchOptionsArray = $patchOptions->getArrayCopy();
                    $request->data = $requestData->getArrayCopy();
                    if ($extra['patchEntity']) {
                        $entity = $model->patchEntity($entity, $request->data, $patchOptionsArray);
                        $event = $model->dispatchEvent('ControllerAction.Model.edit.afterPatch', $params, $this);
                        if ($event->isStopped()) {
                            return $event->result;
                        }
                    }
                    $process = function ($model, $entity) {
                        return $model->save($entity);
                    };

                    $event = $model->dispatchEvent('ControllerAction.Model.edit.beforeSave', [$entity, $requestData, $extra], $this);
                    if ($event->isStopped()) {
                        $mainEvent->stopPropagation();
                        return $event->result;
                    }
                    if (is_callable($event->result)) {
                        $process = $event->result;
                    }
                    $result = $process($model, $entity);

                    if (!$result) {
                        Log::write('debug', $entity->errors());
                    }

                    $event = $model->dispatchEvent('ControllerAction.Model.edit.afterSave', $params, $this);
                    if ($event->isStopped()) {
                        return $event->result;
                    }
                    if ($result) {
                        $mainEvent->stopPropagation();
                        return $model->controller->redirect($model->url('view'));
                    }
                } else {
                    $patchOptions['validate'] = false;
                    $methodKey = 'on' . ucfirst($submit);

                    // Event: addEditOnReload
                    $eventKey = 'ControllerAction.Model.addEdit.' . $methodKey;
                    $method = 'addEdit' . ucfirst($methodKey);
                    $event = $this->dispatchEvent($model, $eventKey, $method, $params);
                    if ($event->isStopped()) {
                        return $event->result;
                    }

                    // Event: editOnReload
                    $eventKey = 'ControllerAction.Model.edit.' . $methodKey;
                    $method = 'edit' . ucfirst($methodKey);
                    $event = $this->dispatchEvent($model, $eventKey, $method, $params);
                    if ($event->isStopped()) {
                        return $event->result;
                    }

                    $patchOptionsArray = $patchOptions->getArrayCopy();
                    $request->data = $requestData->getArrayCopy();
                    $entity = $model->patchEntity($entity, $request->data, $patchOptionsArray);
                }
            }
            $model->controller->set('data', $entity);
        }

        $event = $model->dispatchEvent('ControllerAction.Model.addEdit.afterAction', [$entity, $extra], $this);
        if ($event->isStopped()) {
            return $event->result;
        }

        $event = $model->dispatchEvent('ControllerAction.Model.edit.afterAction', [$entity, $extra], $this);
        if ($event->isStopped()) {
            return $event->result;
        }
        if (!$entity) {
            $mainEvent->stopPropagation();
            return $model->controller->redirect($model->url('index', 'QUERY'));
        }
        return $entity;
    }
}
