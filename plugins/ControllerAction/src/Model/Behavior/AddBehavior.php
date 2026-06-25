<?php
namespace ControllerAction\Model\Behavior;
use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\Http\ServerRequest;
use ControllerAction\Model\Traits\EventTrait;
use Cake\Utility\Text;

class AddBehavior extends Behavior {
    use EventTrait;
    public function implementedEvents(): array {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.add'] = 'add';
        return $events;
    }
    public function add(EventInterface $mainEvent, ArrayObject $extra) {
        $model = $this->_table;
        //POCOR-7485 use this for adminsitration > System Setup > Acadmic Period add starts
        if($model->getAlias() == 'AcademicPeriods'){
            $model->setEntityClass(\Cake\ORM\Entity::class);
        }// end
        $request = $this->_table->request;
        // POCOR-8534 Start
        $addAccess = $this->getAddAccess($request, $model);
        if (!$addAccess) {
//            $logoutUrl = Router::url(['plugin' => 'User', 'controller' => 'Users', 'action' => 'logout'], true);
            return $this->redirectToDashboard($mainEvent, $model);
        }
        // POCOR-8534 End

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
                // $request->data = $requestData->getArrayCopy();//cakephp 3
                // $requestArrayCopyData = $requestData->getArrayCopy();
                $requestCopyData = $request->withParsedBody($requestData->getArrayCopy());//POCOR-7485
                $requestArrayCopyData = $requestCopyData->getData();//POCOR-7485
                if ($extra['patchEntity']) {
                    $entity = $model->patchEntity($entity, $requestArrayCopyData, $patchOptionsArray);
                    $event = $model->dispatchEvent('ControllerAction.Model.add.afterPatch', $params, $this);
                    if ($event->isStopped()) {
                        $mainEvent->stopPropagation();
                        return $event->getResult();
                    }
                }
                //POCOR-8483[START] // major change for this ticket 
                if($request->getAttribute('params')['action'] == 'reportCardGenerate'){
                    if(isset($entity->assessment_id)){
                        $process = function ($model, $entity) {
                            return $model->save($entity);
                        };
                    }
                    $event = $model->dispatchEvent('ControllerAction.Model.add.beforeSave', [$entity, $requestData, $extra], $this);
                    if ($event->isStopped()) {
                        $mainEvent->stopPropagation();
                        return $event->getResult();
                    }
                    if (is_callable($event->getResult())) {
                        $process = $event->getResult();
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
                }else{
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
                }
                 //POCOR-8483[END]
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
                // $requestArrayCopyData = $requestData->getArrayCopy();
                $requestCopyData = $request->withParsedBody($requestData->getArrayCopy());//POCOR-7485
                $requestArrayCopyData = $requestCopyData->getData();//POCOR-7485
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

    /**
     * // POCOR-8534
     * @param EventInterface $mainEvent
     * @param Table $model
     * @return mixed
     */
    private function redirectToDashboard(EventInterface $mainEvent, Table $model)
    {
        $mainEvent->stopPropagation();
        $model->Alert->warning('general.notAccess');
        Log::debug('Undefined Edit Access');
        return $model->controller->redirect(['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index']);
    }

    /**
     * // POCOR-8534
     * @return mixed
     */
    private function getAddAccess($request, Table $model)
    {
        $controllerName = $request->getParam('controller');
        $plugin = $request->getParam('plugin');
        $action = $request->getParam('action');
        $toCheck = [
            'controller' => $controllerName,
            'plugin' => $plugin,
            'action' => $action,
            'add'];
        if ($action == 'add') {
            unset($toCheck['add']);
        }
        if (!$action) {
            unset($toCheck['action']);
        }
        $editAccess = $model->AccessControl->check($toCheck);
        return $editAccess;
    }

}

