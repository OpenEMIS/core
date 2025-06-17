<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Log\Log;
use Cake\Http\ServerRequest;

use ControllerAction\Model\Traits\EventTrait;

class EditBehavior extends Behavior
{
    use EventTrait;

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.edit'] = 'edit';
        return $events;
    }

    public function edit(Event $mainEvent, ArrayObject $extra)
    {
        $model = $this->_table;
        //POCOR-7485 use this for adminsitration > System Setup > Acadmic Period edit starts
        if($model->getAlias() == 'AcademicPeriods'){
            $model->setEntityClass(\Cake\ORM\Entity::class);
        }// end
        // POCOR-8543 START

        $request = $model->request;
        $editAccess = $this->getEditAccess($request, $model);
        if (!$editAccess) {
//            $logoutUrl = Router::url(['plugin' => 'User', 'controller' => 'Users', 'action' => 'logout'], true);
            return $this->redirectToDashboard($mainEvent, $model);
        }
        // POCOR-8543 END
        $extra['config']['form'] = true;
        $extra['patchEntity'] = true;

        $event = $model->dispatchEvent('ControllerAction.Model.addEdit.beforeAction', [$extra], $this);
        if ($event->isStopped()) {
            return $event->getResult();
        }
        if ($event->getResult() instanceof Table) {
            $model = $event->getResult();
        }

        $event = $model->dispatchEvent('ControllerAction.Model.edit.beforeAction', [$extra], $this);
        if ($event->isStopped()) {
            return $event->getResult();
        }
        if ($event->getResult() instanceof Table) {
            $model = $event->getResult();
        }

        $viewRequest = $model->controller->getRequest();
        $viewParam = $model->controller->getRequest()->getAttribute('params')['pass'];
        unset($viewParam[1]);
        $paramsPass = array_values($viewParam);
        //comment cakephp4 edit was not working
        $ids = empty($model->paramsPass(0)) ? [] : $model->paramsDecode($model->paramsPass(0));
        //$ids = empty($paramsPass) ? [] : $model->paramsDecode($paramsPass[1]);
        $sessionKey = $model->getRegistryAlias() . '.primaryKey';
        if (empty($ids)) {
            if ($model->Session->check($sessionKey)) {
                $ids = $model->Session->read($sessionKey);
            } else if (!empty($model->ControllerAction->getQueryString())) {
                // Query string logic not implemented yet, will require to check if the query string contains the primary key
                $primaryKey = $model->getPrimaryKey();
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
            return $event->getResult();
        }

        $event = $model->dispatchEvent('ControllerAction.Model.edit.afterQuery', [$entity, $extra], $this);
        if ($event->isStopped()) {
            return $event->getResult();
        }

        if ($entity) {
            if ($request->is(['get'])) {
                $event = $model->dispatchEvent('ControllerAction.Model.edit.onInitialize', [$entity, $extra], $this);
                if ($event->isStopped()) {
                    return $event->getResult();
                }
            } else if ($request->is(['post', 'put'])) {
                $submit = ($request->getData('submit') !== null) ? $request->getData('submit') : 'save';
                $patchOptions = new ArrayObject([]);
                $requestData = new ArrayObject($request->getData());

                $params = [$entity, $requestData, $patchOptions, $extra];

                if ($submit == 'save') {
                    $event = $model->dispatchEvent('ControllerAction.Model.addEdit.beforePatch', $params, $this);
                    if ($event->isStopped()) {
                        return $event->getResult();
                    }

                    $event = $model->dispatchEvent('ControllerAction.Model.edit.beforePatch', $params, $this);
                    if ($event->isStopped()) {
                        return $event->getResult();
                    }

                    $patchOptionsArray = $patchOptions->getArrayCopy();
                    //$requestCopyData = $requestData->getArrayCopy();
                    $requestArrayCopyData = $request->withParsedBody($requestData->getArrayCopy());//POCOR-7485
                    $requestCopyData = $requestArrayCopyData->getData();//POCOR-7485
                    if ($extra['patchEntity']) {
                        $entity = $model->patchEntity($entity, $requestCopyData, $patchOptionsArray);
                        $event = $model->dispatchEvent('ControllerAction.Model.edit.afterPatch', $params, $this);
                        if ($event->isStopped()) {
                            return $event->getResult();
                        }
                    }
                    $process = function ($model, $entity) {
                        // POCOR-9101 start // don't save changes to main contacts?
                        if ($entity->get('email') === '') {
                            $entity->unset('email');
                        }
                        if ($entity->get('mobile_number') === '') {
                            $entity->unset('mobile_number');
                        }
                        // POCOR-9101 end
                        return $model->save($entity);
                    };

                    $event = $model->dispatchEvent('ControllerAction.Model.edit.beforeSave', [$entity, $requestData, $extra], $this);
                    if ($event->isStopped()) {
                        $mainEvent->stopPropagation();
                        return $event->getResult();
                    }
                    if (is_callable($event->getResult())) {
                        $process = $event->getResult();
                    }
                    $result = $process($model, $entity);

                    if (!$result) {
                        $errorString = json_encode($entity->getErrors());
                        Log::write('debug', $errorString);
                    }

                    $event = $model->dispatchEvent('ControllerAction.Model.edit.afterSave', $params, $this);
                    if ($event->isStopped()) {
                        return $event->getResult();
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
                        return $event->getResult();
                    }

                    // Event: editOnReload
                    $eventKey = 'ControllerAction.Model.edit.' . $methodKey;
                    $method = 'edit' . ucfirst($methodKey);
                    $event = $this->dispatchEvent($model, $eventKey, $method, $params);
                    if ($event->isStopped()) {
                        return $event->getResult();
                    }
                    $patchOptionsArray = $patchOptions->getArrayCopy();
                    $requestCopyData = $requestData->getArrayCopy();
                    $entity = $model->patchEntity($entity, $requestCopyData, $patchOptionsArray);
                }
            }
            $model->controller->set('data', $entity);
        }

        $event = $model->dispatchEvent('ControllerAction.Model.addEdit.afterAction', [$entity, $extra], $this);
        if ($event->isStopped()) {
            return $event->getResult();
        }

        $event = $model->dispatchEvent('ControllerAction.Model.edit.afterAction', [$entity, $extra], $this);
        if ($event->isStopped()) {
            return $event->getResult();
        }
        if (!$entity) {
            $mainEvent->stopPropagation();
            return $model->controller->redirect($model->url('index', 'QUERY'));
        }
        return $entity;
    }


    /**
     * // POCOR-8534
     * @param Event $mainEvent
     * @param Table $model
     * @return mixed
     */
    private function redirectToDashboard(Event $mainEvent, Table $model)
    {
        $mainEvent->stopPropagation();
        $model->Alert->warning('general.notAccess');
        Log::debug('Undefined Edit Access');
        return $model->controller->redirect(['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index']);
    }

    /**
     * @return mixed
     */
    private function getEditAccess($request, Table $model)
    {

        $controllerName = $request->getParam('controller');
        $plugin = $request->getParam('plugin');
        $action = $request->getParam('action');
        $toCheck = [
            'controller' => $controllerName,
            'plugin' => $plugin,
            'action' => $action,
            'edit'];
        if ($action == 'edit') {
            unset($toCheck['edit']);
        }
        if (!$action) {
            unset($toCheck['action']);
        }
        $editAccess = $model->AccessControl->check($toCheck);
        return $editAccess;
    }

}
