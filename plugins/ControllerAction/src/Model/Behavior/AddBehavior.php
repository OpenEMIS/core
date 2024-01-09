<?php
namespace ControllerAction\Model\Behavior;
use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\Http\ServerRequest;
use ControllerAction\Model\Traits\EventTrait;
class AddBehavior extends Behavior {
    use EventTrait;
    public function implementedEvents(): array {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.add'] = 'add';
        return $events;
    }
    public function add(Event $mainEvent, ArrayObject $extra) {
        $model = $this->table();
        $request = $this->table()->request;
        $extra['config']['form'] = true;
        $extra['patchEntity'] = true;
        $extra['redirect'] = $model->url('index', 'QUERY');
        $event = $model->dispatchEvent('ControllerAction.Model.addEdit.beforeAction', [$extra], $this);
        if ($event->isStopped()) {
            $mainEvent->stopPropagation();
            return $event->getResult();
        }
        if ($event->getResult() instanceof Table) {
            $model = $event->getResult();
        }
        $event = $model->dispatchEvent('ControllerAction.Model.add.beforeAction', [$extra], $this);
        if ($event->isStopped()) {
            $mainEvent->stopPropagation();
            return $event->getResult();
        }
        if ($event->getResult() instanceof Table) {
            $model = $event->getResult();
        }
       //cakephp4 add blank array
        //$entity = $model->newEntity();
        $entity = $model->newEmptyEntity();
        $event = $model->dispatchEvent('ControllerAction.Model.add.onInitialize', [$entity, $extra], $this);
            if ($event->isStopped()) {
                $mainEvent->stopPropagation();
                return $event->getResult();
            }
        if ($request->is(['get'])) {
            $event = $model->dispatchEvent('ControllerAction.Model.add.onInitialize', [$entity, $extra], $this);
            if ($event->isStopped()) {
                $mainEvent->stopPropagation();
                return $event->getResult();
            }
        } else if ($request->is(['post', 'put'])) {
            $submit = isset($request->getdata()['submit']) ? $request->getdata()['submit'] : 'save';
            $patchOptions = new ArrayObject([]);
            $requestData = new ArrayObject($request->getData());
            $params = [$entity, $requestData, $patchOptions, $extra];
            if ($submit == 'save') {
                $event = $model->dispatchEvent('ControllerAction.Model.addEdit.beforePatch', $params, $this);
                if ($event->isStopped()) {
                    $mainEvent->stopPropagation();
                    return $event->getResult();
                }
                $event = $model->dispatchEvent('ControllerAction.Model.add.beforePatch', $params, $this);
                if ($event->isStopped()) {
                    $mainEvent->stopPropagation();
                    return $event->getResult();
                }
                $patchOptionsArray = $patchOptions->getArrayCopy();
                //$request->data = $requestData->getArrayCopy();
                $requestArrayCopyData = $requestData->getArrayCopy();
                if ($extra['patchEntity']) {
                   // $entity = $model->patchEntity($entity, $request->data, $patchOptionsArray);
                    $entity = $model->patchEntity($entity, $requestArrayCopyData, $patchOptionsArray);

                    $event = $model->dispatchEvent('ControllerAction.Model.add.afterPatch', $params, $this);
                    if ($event->isStopped()) {
                        $mainEvent->stopPropagation();
                        return $event->getResult();
                    }
                }
                $process = function ($model, $entity) {
                    return $model->save($entity);
                };
                $event = $model->dispatchEvent('ControllerAction.Model.add.beforeSave', [$entity, $requestData, $extra], $this);
                if ($event->isStopped()) {
                    $mainEvent->stopPropagation();
                    return $event->getResult();
                }
                if (is_callable($event->getResult())) {
                    $process = $event->getResult();
                }
                $result = $process($model, $entity);
                if (!$result) {
                    $errors = $entity->getErrors();
                    $errorString = json_encode($errors);
                    Log::write('debug', $errorString);
                }
                $event = $model->dispatchEvent('ControllerAction.Model.add.afterSave', [$entity, $requestData, $extra], $this);
                if ($event->isStopped()) {
                    $mainEvent->stopPropagation();
                    return $event->getResult();
                }
                if ($result && $extra['redirect'] !== false) {
                    $mainEvent->stopPropagation();
                    return $model->controller->redirect($extra['redirect']);
                }
            } else {
                $patchOptions['validate'] = false;
                $methodKey = 'on' . ucfirst($submit);
                $eventKey = 'ControllerAction.Model.addEdit.' . $methodKey;
                $method = 'addEdit' . ucfirst($methodKey);
                $event = $this->dispatchEvent($model, $eventKey, $method, $params);
                if ($event->isStopped()) {
                    $mainEvent->stopPropagation();
                    return $event->getResult();
                }
                $eventKey = 'ControllerAction.Model.add.' . $methodKey;
                $method = 'add' . ucfirst($methodKey);
                $event = $this->dispatchEvent($model, $eventKey, $method, $params);
                if ($event->isStopped()) {
                    $mainEvent->stopPropagation();
                    return $event->getResult();
                }
                $patchOptionsArray = $patchOptions->getArrayCopy();
                // $request->data = $requestData->getArrayCopy();
                $requestArrayCopyData = $requestData->getArrayCopy();
                $entity = $model->patchEntity($entity, $requestArrayCopyData, $patchOptionsArray);
            }
        }
        $event = $model->dispatchEvent('ControllerAction.Model.addEdit.afterAction', [$entity, $extra], $this);
        if ($event->isStopped()) {
            $mainEvent->stopPropagation();
            return $event->getResult();
        }
        $event = $model->dispatchEvent('ControllerAction.Model.add.afterAction', [$entity, $extra], $this);
        if ($event->isStopped()) {
            $mainEvent->stopPropagation();
            return $event->getResult();
        }
        $model->controller->set('data', $entity);
        return $entity;
    }
    
}

