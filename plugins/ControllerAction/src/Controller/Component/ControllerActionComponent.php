<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should
have received a copy of the GNU General Public License along with this program.  If not, see
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.

ControllerActionComponent - Current Version 3.1.22
3.1.23 (Malcolm) - For 'ControllerAction.Model.index.beforeAction' removed $query from parameters and added it to $settings['query']
3.1.22 (Jeff) - Fixed a bug that caused out of memory when performing a delete on Restrict Delete strategy
3.1.21 (Zack) - Amended restrict delete page and remove (event - ControllerAction.Model.onBeforeRestrictDelete). To use (event - deleteOnInitialize) instead.
3.1.20 (Malcolm) - Added (deleteStrategy type - 'restrict') and (event - ControllerAction.Model.onBeforeRestrictDelete)
3.1.19 (Malcolm) - Fixed an error issue when using getFields() on tables with joint primary keys
3.1.18 (Malcolm) - remove() - If id(to be deleted) cannot be found, return a successful deletion message
3.1.17 (Malcolm) - buildDefaultValidation() - Added condition '&& strlen($attr['default']) == 0' when it comes to determining whether should automatically add 'notBlank' and 'requirePresence' validations
3.1.16 (Malcolm) - renderFields() - '-- Select --' is added if ($attr['type'] != 'chosenSelect')
3.1.15 (Malcolm) - renderFields() - for automatic adding of '-- Select --' if (there are no '' value fields in dropdown) and $attr['select'] != false (default true)
3.1.14 (Malcolm) - supported default selection for select boxes - renderFields() edit
3.1.13 (Thed) - added new event editAfterQuery to modified $entity after query is executed
3.1.12 (Zack) - added new event onGetConvertOptions to add additional condition to the query to generate the convert options for delete and transfer
3.1.11 (Zack) - added logic to reorder() to swap the order of the list that is pass over with the original list
3.1.10 (Thed) - added new event onDeleteTransfer
3.1.9 (Malcolm) - Added 'getTriggerFrom()' get method
3.1.8 (Jeff) - session variable to store the primary key value of object includes the plugin name now
3.1.7 (Jeff) - added properties $view and function renderView() so that custom view can be rendered with all events triggered
3.1.6 (Jeff) - created function url($action) to return url with params
3.1.5 (Jeff) - moved initButtons to afterAction so that query params can be passed to buttons
3.1.4 (Jeff) - removed $controller param from addAfterSave and replaced with $requestData
3.1.3 (Jeff) - added new event deleteBeforeAction
3.1.2 (Jeff) - added deleteStrategy for transferring of records, new event deleteOnInitialize
3.1.1 (Jeff) - modified add(), edit() to allow changing of table
3.1.0 (Jeff) - moved renderFields() to be called after the event (afterAction) is triggered
3.0.9 (Jeff) - fixed getContains to retrieve only id, name and foreignKeys fields
3.0.8 (Jeff) - fixed remove() not throwing errors if delete fails
3.0.7 (Jeff) - edited ControllerAction.Controller.beforePaginate to use $this->model instead of $model
3.0.6 (Malcolm) - $request->data = $requestData->getArrayCopy(); added after addAfterPatch dispatch event
             - for purpose of modifying request->data after validation (eg. unsetting a field the value can be removed from the input field after validation)
3.0.5 (Jeff) - renamed beforeRender to afterAction, afterAction is called in processAction() now.
             - this change is necessary to be compatible with CakePHP v3.1.0
             - optimized getContains to only fetch id and name instead of all fields which are not being used most of the time
3.0.4 (Jeff) - added sortable types in renderFields() to be able to sort by date/time
3.0.3 (Jeff) - added in search() to implement auto_contain|auto_search|auto_order options to be used in indexBeforePaginate
3.0.2 (Jeff) - removed debug message on event (ControllerAction.Model.onPopulateSelectOptions)
3.0.1 (Jeff) - add debug messages on all events triggered by this component
*/

namespace ControllerAction\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Network\Response;
use Cake\Network\Exception\NotFoundException;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\I18n\I18n;
use Cake\Core\Configure;
use Cake\Utility\Security;
use Cake\Controller\Exception\SecurityException;
use Cake\Log\Log;

use ControllerAction\Model\Traits\ControllerActionV4Trait;
use ControllerAction\Model\Traits\SecurityTrait;

class ControllerActionComponent extends Component
{
    use ControllerActionV4Trait; // extended functionality from v4
    use SecurityTrait;

    private $plugin;
    private $controller;
    private $triggerFrom = 'Controller';
    private $currentAction;
    private $ctpFolder;
    private $paramsPass;
    private $config;
    private $defaultActions = ['search', 'index', 'add', 'view', 'edit', 'remove', 'download', 'reorder'];
    private $deleteStrategy = 'cascade'; // cascade | transfer | restrict
    private $view = '';

    public $model = null;
    public $models = [];
    public $buttons = [];
    public $orderField = 'order';
    public $autoRender = true;
    public $autoProcess = true;
    public $ignoreFields = ['modified', 'created'];
    public $templatePath = '/ControllerAction/';
    public $pageOptions = [10, 20, 30, 40, 50];
    public $Session;
    public $debug = false;

    public $components = ['ControllerAction.Alert', 'Paginator'];

    private $cakephpReservedPassKeys = [
            'controller',
            'action',
            'plugin',
            'pass',
            '_matchedRoute',
            '_Token',
            '_csrfToken',
            'paging'
        ];

    // Is called before the controller's beforeFilter method.
    public function initialize(array $config)
    {
        if (array_key_exists('templates', $config)) {
            $this->templatePath = $config['templates'];
        }
        if (array_key_exists('ignoreFields', $config)) {
            $this->ignoreFields = array_merge($this->ignoreFields, $config['ignoreFields']);
        }
        $controller = $this->_registry->getController();
        $this->paramsPass = $this->request->params['pass'];
        $this->currentAction = $this->request->params['action'];
        $this->ctpFolder = $controller->name;

        $this->controller = $controller;
        $this->Session = $this->request->session();
        $this->config = new ArrayObject([]);

        $this->debug = Configure::read('debug');
    }

    // Is called after the controller's beforeFilter method but before the controller executes the current action handler.
    public function startup(Event $event)
    {
        $controller = $this->controller;

        $action = $this->request->params['action'];
        $this->debug('Startup');
        if (!method_exists($controller, $action)) { // method cannot be found in controller
            $defaultActions = $this->defaultActions;

            if (!is_null($this->model)) {
                // Trigger event to model to modify defaultActions
                $this->debug(__METHOD__, ': Event -> ControllerAction.Model.onUpdateDefaultActions');
                $event = new Event('ControllerAction.Model.onUpdateDefaultActions', $this);
                $event = $this->model->eventManager()->dispatch($event);
                if ($event->isStopped()) {
                    return $event->result;
                }

                if (!empty($event->result)) {
                    $newDefaultActions = $event->result;
                    $defaultActions = array_merge($defaultActions, $newDefaultActions);
                }
            }

            $this->defaultActions = $defaultActions;

            if (in_array($action, $this->defaultActions)) { // default actions
                $this->currentAction = $action;
                $this->request->params['action'] = 'ComponentAction';
                $this->initComponentsForModel();
            } else { // check if it's a model action
                $this->debug(__METHOD__, ': Searching Models');
                foreach ($this->models as $name => $attr) {
                    if (strtolower($action) === strtolower($name)) { // model class found
                        $this->debug(__METHOD__, ': ' . $name . ' found');
                        $this->debug(__METHOD__, ': ControllerAction v3 has been deprecated, please convert '. $name . ' to v4');
                        $currentAction = 'index';
                        if (!empty($this->paramsPass)) {
                            $currentAction = array_shift($this->paramsPass);
                        }

                        $actions = isset($attr['actions']) ? $attr['actions'] : $this->defaultActions;
                        $_options = ['deleteStrategy' => 'cascade'];

                        if (isset($attr['options'])) {
                            $_options = array_merge($_options, $attr['options']);
                        }

                        $this->model($attr['className'], $actions, $_options);
                        $this->model->alias = $name;
                        $this->currentAction = $currentAction;
                        $this->ctpFolder = $this->model->alias();
                        $this->request->params['action'] = 'ComponentAction';
                        $this->initComponentsForModel();

                        $this->debug(__METHOD__, ': Event -> ControllerAction.Controller.onInitialize');
                        $event = new Event('ControllerAction.Controller.onInitialize', $this, [$this->model, new ArrayObject([])]);
                        $event = $this->controller->eventManager()->dispatch($event);
                        if ($event->isStopped()) {
                            return $event->result;
                        }

                        $this->triggerFrom = 'Model';
                        break;
                    }
                }
            }
        }

        $pass = $this->request->pass;
        if (isset($pass[0])) {
            if ($pass[0] == 'reorder') {
                $this->enableReorder($this->request->params['action'], $controller);
            }
        } elseif ($action == 'reorder') {
            $this->enableReorder($this->request->params['action'], $controller);
        }
    }

    private function enableReorder($action, $controller)
    {
        if ($this->request->is('post')) {
            $token = isset($this->request->cookies['csrfToken']) ? $this->request->cookies['csrfToken'] : '';
            $this->request->env('HTTP_X_CSRF_TOKEN', $token);
        }
        $controller->Security->config('unlockedActions', [
            $action
        ]);
    }

    public function renderFields()
    {
        foreach ($this->model->fields as $key => $attr) {
            if ($key == $this->orderField) {
                $this->model->fields[$this->orderField]['visible'] = ['view' => false];
            }
            if (array_key_exists('options', $attr)) {
                if (in_array($attr['type'], ['string', 'integer'])) {
                    $this->model->fields[$key]['type'] = 'select';
                }
                if (empty($attr['options']) && empty($attr['attr']['empty'])) {
                    if (!array_key_exists('empty', $attr)) {
                        $this->model->fields[$key]['attr']['empty'] = $this->Alert->getMessage('general.select.noOptions');
                    }
                }

                // for automatic adding of '-- Select --' if there are no '' value fields in dropdown
                $addSelect = true;
                if ($attr['type'] == 'chosenSelect') {
                    $addSelect = false;
                }
                if (array_key_exists('select', $attr)) {
                    if ($attr['select'] === false) {
                        $addSelect = false;
                    } else {
                        $addSelect = true;
                    }
                }
                if ($addSelect) {
                    if (is_array($attr['options'])) {
                        // need to check if options has any ''
                        if (!array_key_exists('', $attr['options'])) {
                            $this->model->fields[$key]['options'] = ['' => __('-- Select --')] + $attr['options'];
                        }
                    }
                }
            }

            // make field sortable by default if it is a string data-type
            if (!array_key_exists('type', $attr)) {
                $this->log($key, 'debug');
                continue;
            }

            $sortableTypes = ['string', 'date', 'time', 'datetime'];
            if (in_array($attr['type'], $sortableTypes) && !array_key_exists('sort', $attr) && $this->model->hasField($key)) {
                $this->model->fields[$key]['sort'] = true;
            } elseif ($attr['type'] == 'select' && !array_key_exists('options', $attr)) {
                if ($this->isForeignKey($key)) {
                    // $associatedObjectName = Inflector::pluralize(str_replace('_id', '', $key));
                    // $associatedObject = $this->model->{Inflector::camelize($associatedObjectName)};
                    $associatedObject = $this->getAssociatedBelongsToModel($key);

                    $query = $associatedObject->find();

                    $event = new Event('ControllerAction.Model.onPopulateSelectOptions', $this, [$query]);
                    $event = $associatedObject->eventManager()->dispatch($event);
                    if ($event->isStopped()) {
                        return $event->result;
                    }
                    if (!empty($event->result)) {
                        $query = $event->result;
                    }

                    if ($query instanceof Query) {
                        $queryData = $query->toArray();
                        $hasDefaultField = false;
                        $defaultValue = false;
                        $optionsArray = [];
                        foreach ($queryData as $okey => $ovalue) {
                            $optionsArray[$ovalue->id] = $ovalue->name;
                            if ($ovalue->has('default')) {
                                $hasDefaultField = true;
                                if ($ovalue->default) {
                                    $defaultValue = $ovalue->id;
                                }
                            }
                        }

                        if (!empty($defaultValue)) {
                            $this->model->fields[$key]['default'] = $defaultValue;
                        }
                        if ($attr['type'] != 'chosenSelect') {
                            $optionsArray = ['' => __('-- Select --')] + $optionsArray;
                        }

                        $this->model->fields[$key]['options'] = $optionsArray;
                    } else {
                        $this->model->fields[$key]['options'] = $query;
                    }
                }
            }

            if (array_key_exists('onChangeReload', $attr)) {
                if (!array_key_exists('attr', $this->model->fields[$key])) {
                    $this->model->fields[$key]['attr'] = [];
                }
                $onChange = '';
                if (is_bool($attr['onChangeReload']) && $attr['onChangeReload'] == true) {
                    $onChange = "$('#reload').click();return false;";
                } else {
                    $onChange = "$('#reload').val('" . $attr['onChangeReload'] . "').click();return false;";
                }
                $this->model->fields[$key]['attr']['onchange'] = $onChange;
            }
        }
    }

    public function model($model=null, $actions=[], $options=[])
    {
        if (array_key_exists('deleteStrategy', $options)) {
            $this->deleteStrategy = $options['deleteStrategy'];
        }

        if (is_null($model)) {
            return $this->model;
        } else {
            if (!empty($actions)) {
                // removing actions
                // may not be the perfect solution yet
                foreach ($actions as $action) {
                    $splitStr = str_split($action);
                    if ($splitStr[0] == '!') {
                        foreach ($this->defaultActions as $i => $val) {
                            if ($val == substr($action, 1, strlen($action))) {
                                unset($this->defaultActions[$i]);
                                break;
                            }
                        }
                    } else {
                        $this->defaultActions = $actions;
                        break;
                    }
                }
            }
            $this->plugin = $this->getPlugin($model);
            $this->model = $this->controller->loadModel($model);
            $this->model->alias = $this->model->alias();
            $this->getFields($this->model);
        }
    }

    public function action()
    {
        return $this->currentAction;
    }

    public function removeDefaultActions(array $actions)
    {
        $defaultActions = $this->defaultActions;
        foreach ($actions as $action) {
            if (array_search($action, $defaultActions)) {
                unset($defaultActions[array_search($action, $defaultActions)]);
            }
        }
        $this->defaultActions = $defaultActions;
    }

    public function addDefaultActions(array $actions)
    {
        $defaultActions = $this->defaultActions;
        foreach ($actions as $action) {
            if (! array_search($action, $defaultActions)) {
                $defaultActions[] = $action;
            }
        }
        $this->defaultActions = $defaultActions;
    }

    public function vars()
    {
        return $this->controller->viewVars;
    }

    public function getVar($key)
    {
        $value = null;
        if (isset($this->controller->viewVars[$key])) {
            $value = $this->controller->viewVars[$key];
        }
        return $value;
    }

    public function paramsPass()
    {
        $params = $this->request->pass;
        if ($this->triggerFrom == 'Model') {
            unset($params[0]);
        }
        return $params;
    }

    public function paramsQuery()
    {
        return $this->request->query;
    }

    public function params()
    {
        $params = $this->paramsPass();
        return array_merge($params, $this->paramsQuery());
    }

    private function mergeRequestParams(array &$url)
    {
        $requestParams = $this->request->params;
        foreach ($requestParams as $key => $value) {
            if (is_numeric($key) || in_array($key, $this->cakephpReservedPassKeys)) {
                unset($requestParams[$key]);
            }
        }
        $url = array_merge($url, $requestParams);
    }

    public function url($action, $params = true /* 'PASS' | 'QUERY' | false */)
    {
        $controller = $this->controller;
        $url = ['plugin' => $controller->plugin, 'controller' => $controller->name];

        if ($this->triggerFrom == 'Model') {
            $url['action'] = $this->model->alias;
            $url[0] = $action;
        } else {
            $url['action'] = $action;
        }

        $this->mergeRequestParams($url);

        if ($params === true) {
            $url = array_merge($url, $this->params());
        } elseif ($params === 'PASS') {
            $url = array_merge($url, $this->paramsPass());
        } elseif ($params === 'QUERY') {
            $url = array_merge($url, $this->paramsQuery());
        }
        return $url;
    }

    public function buildDefaultValidation()
    {
        $action = $this->currentAction;
        if ($action != 'index' && $action != 'view') {
            $validator = $this->model->validator();
            foreach ($this->model->fields as $key => $attr) {
                if ($validator->hasField($key)) {
                    $set = $validator->field($key);

                    if (!$set->isEmptyAllowed()) {
                        $set->add('notBlank', ['rule' => 'notBlank']);
                    }
                    if (!$set->isPresenceRequired()) {
                        if ($this->isForeignKey($key)) {
                            $validator->requirePresence($key);
                        }
                    }
                } else { // field not presence in validator
                    if (array_key_exists('null', $attr)) {
                        if ($attr['null'] === false // not nullable
                            && (array_key_exists('default', $attr) && strlen($attr['default']) == 0) // don't have a default value in database
                            && $key !== 'id' // not a primary key
                            && !in_array($key, $this->ignoreFields) // fields not excluded
                        ) {
                            $validator->add($key, 'notBlank', ['rule' => 'notBlank']);
                            if ($this->isForeignKey($key)) {
                                $validator->requirePresence($key);
                            }
                        }
                    }
                }
            }
        }
    }

    public function isForeignKey($field)
    {
        $model = $this->model;
        foreach ($model->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // belongsTo associations
                if ($field === $assoc->foreignKey()) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getAssociatedBelongsToModel($field)
    {
        $relatedModel = null;

        foreach ($this->model->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // belongsTo associations
                if ($field === $assoc->foreignKey()) {
                    $relatedModel = $assoc;
                    break;
                }
            }
        }
        return $relatedModel;
    }

    public function getAssociatedEntityArrayKey($field)
    {
        $associationKey = $this->getAssociatedBelongsToModel($field);
        $associatedEntityArrayKey = null;
        if (is_object($associationKey)) {
            $associatedEntityArrayKey = Inflector::underscore(Inflector::singularize($associationKey->alias()));
        } else {
            die($field . '\'s association not found in ' . $this->model->alias());
        }
        return $associatedEntityArrayKey;
    }

    public function getPrimaryKey(Table $model)
    {
        $primaryKey = $model->primaryKey();
        if (is_array($primaryKey)) {
            $primaryKey = 'id';
        }
        return $primaryKey;
    }

    private function initButtons()
    {
        $controller = $this->controller;

        $named = $this->request->query;
        $pass = $this->request->params['pass'];
        $extra = new ArrayObject([]);
        if ($this->triggerFrom == 'Model') {
            unset($pass[0]);
        }
        $defaultUrl = ['plugin' => $controller->plugin, 'controller' => $controller->name];
        $this->mergeRequestParams($defaultUrl);

        $buttons = new ArrayObject([]);

        foreach ($this->defaultActions as $action) {
            $actionUrl = $defaultUrl;
            $actionUrl['action'] = $action;

            if ($this->triggerFrom == 'Model') {
                $actionUrl['action'] = $this->model->alias;
                $actionUrl[0] = $action;
            }

            if ($action != 'index') {
                if ($this->currentAction != 'index') {
                    $model = $this->model;
                    $sessionKey = $model->registryAlias() . '.primaryKey';
                    $extra['primaryKeyValue'] = $this->paramsEncode($this->Session->read($sessionKey));
                    if (empty($pass)) {
                        if ($this->Session->check($sessionKey)) {
                            $pass = [$extra['primaryKeyValue']];
                        }
                    } elseif (isset($pass[0]) && $pass[0]==$action) {
                        if ($this->Session->check($sessionKey)) {
                            $pass[1] = $extra['primaryKeyValue'];
                        }
                    }
                }
                $actionUrl = array_merge($actionUrl, $pass);
            }
            $actionUrl = array_merge($actionUrl, $named);
            $buttons[$action] = array('url' => $actionUrl);
        }

        $backAction = 'index';
        if ($this->currentAction == 'edit' || $this->currentAction == 'remove') {
            $backAction = 'view';
        }

        $backUrl = $defaultUrl;
        $backUrl['action'] = $backAction;
        if ($this->triggerFrom == 'Model') {
            $backUrl['action'] = $this->model->alias;
            $backUrl[] = $backAction;
        }
        if ($backAction != 'index') {
            $backUrl = array_merge($backUrl, $pass);
        }
        $backUrl = array_merge($backUrl, $named);
        $buttons['back'] = array('url' => $backUrl);
        if ($buttons->offsetExists('remove')) {
            $buttons['remove']['strategy'] = $this->deleteStrategy;
        }

        // logic for Reorder buttons
        $schema = $this->getSchema($this->model);
        if (!is_null($this->model) && array_key_exists($this->orderField, $schema)) {
            $reorderUrl = $defaultUrl;
            $reorderUrl['action'] = 'reorder';
            $reorderUrl = array_merge($reorderUrl, $named, $pass);
            $buttons['reorder'] = array('url' => $reorderUrl);
        } else {
            if (array_key_exists('reorder', $buttons)) {
                unset($buttons['reorder']);
            }
        }

        $params = [$buttons, $this->currentAction, $this->triggerFrom == 'Model', $extra];
        $this->debug(__METHOD__, ': Event -> ControllerAction.Model.onInitializeButtons');
        $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.onInitializeButtons', null, $params);
        if ($event->isStopped()) {
            return $event->result;
        }

        $this->buttons = $buttons->getArrayCopy();
    }

    private function initComponentsForModel()
    {
        $this->debug(__METHOD__);
        $this->model->controller = $this->controller;
        $this->model->request = $this->request;
        $this->model->Session = $this->request->session();
        $this->model->action = $this->currentAction;

        // Copy all component objects from Controller to Model
        $components = $this->controller->components()->loaded();
        foreach ($components as $component) {
            $this->model->{$component} = $this->controller->{$component};
        }
    }

    public function processAction()
    {
        $result = null;

        $this->debug(__METHOD__, ': Event -> ControllerAction.Model.beforeAction');
        $event = new Event('ControllerAction.Model.beforeAction', $this);
        $event = $this->model->eventManager()->dispatch($event);
        if ($event->isStopped()) {
            return $event->result;
        }
        $this->buildDefaultValidation();

        if ($this->autoProcess) {
            if ($this->triggerFrom == 'Controller') {
                if (in_array($this->currentAction, $this->defaultActions)) {
                    if (method_exists($this->model, $this->currentAction) || $this->model->behaviors()->hasMethod($this->currentAction)) {
                        $result = call_user_func_array([$this->model, $this->currentAction], $this->paramsPass);
                    } else {
                        $result = call_user_func_array([$this, $this->currentAction], $this->paramsPass);
                    }
                }
            } elseif ($this->triggerFrom == 'Model') {
                if (method_exists($this->model, $this->currentAction) || $this->model->behaviors()->hasMethod($this->currentAction)) {
                    $result = call_user_func_array([$this->model, $this->currentAction], $this->paramsPass);
                } else {
                    if (in_array($this->currentAction, $this->defaultActions)) {
                        $result = call_user_func_array([$this, $this->currentAction], $this->paramsPass);
                    } else {
                        return $this->controller->redirect(['action' => $this->model->alias]);
                    }
                }
            }
            if ($result instanceof Response) {
                return $result;
            }
        }
        $this->debug('processAction');
        $this->afterAction();

        if (!$result instanceof Response) {
            $this->render();
        }
        return $result;
    }

    public function afterAction()
    {
        $controller = $this->controller;
        if (!is_null($this->model) && !empty($this->model->fields)) {
            $action = $this->triggerFrom == 'Model' ? $this->model->alias : $this->currentAction;

            $this->initButtons();

            $this->config['action'] = $this->currentAction;
            $this->config['table'] = $this->model;
            $this->config['fields'] = $this->model->fields;
            $this->config['buttons'] = $this->buttons;
            if (!array_key_exists('formButtons', $this->config)) {
                $this->config['formButtons'] = true; // need better solution
            }

            $this->debug(__METHOD__, ': Event -> ControllerAction.Model.afterAction');
            $event = new Event('ControllerAction.Model.afterAction', $this, [$this->config]);
            $event = $this->model->eventManager()->dispatch($event);
            if ($event->isStopped()) {
                return $event->result;
            }

            $this->renderFields();

            $this->request->params['action'] = $action;

            uasort($this->model->fields, [$this, 'sortFields']);
            $this->config['fields'] = $this->model->fields;

            $controller->set('ControllerAction', $this->config);

            // deprecated: backward compatible
            $controller->set('action', $this->currentAction);
            $controller->set('model', $this->model->alias());
        }
    }

    public function render()
    {
        if (empty($this->plugin)) {
            $path = APP . 'Template' . DS . $this->controller->name . DS;
        } else {
            $path = ROOT . DS . 'plugins' . DS . $this->plugin . DS . 'src' . DS . 'Template' . DS;
        }
        $ctp = $this->ctpFolder . DS . $this->currentAction;

        if (file_exists($path . DS . $ctp . '.ctp')) {
            if ($this->autoRender) {
                $this->autoRender = false;
                $this->controller->render($ctp);
            }
        }

        if ($this->autoRender) {
            if (empty($this->view)) {
                $view = $this->currentAction == 'add' ? 'edit' : $this->currentAction;
                $this->controller->render($this->templatePath . $view);
            } else {
                $this->controller->render($this->view);
            }
        }
    }

    public function renderView($view)
    {
        $this->view = $view;
    }

    public function getModalOptions($type)
    {
        $modal = [];

        if ($type == 'remove' && in_array($type, $this->defaultActions)) {
            $modal['title'] = $this->model->getHeader($this->model->alias());
            $modal['content'] = __('All associated information related to this record will also be removed.');
            $modal['content'] .= '<br><br>';
            $modal['content'] .= __('Are you sure you want to delete this record?');

            $modal['form'] = [
                'model' => $this->model,
                'formOptions' => ['type' => 'delete', 'url' => $this->url('remove')],
                'fields' => ['primaryKey' => ['type' => 'hidden', 'id' => 'recordId', 'unlockField' => true]]
            ];

            $modal['buttons'] = [
                '<button type="submit" class="btn btn-default">' . __('Delete') . '</button>'
            ];
            $modal['cancelButton'] = true;
        }
        return $modal;
    }

    public function getContains($model, $type = 'belongsTo')
    { // type is not being used atm
        $contain = [];
        foreach ($model->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // only contain belongsTo associations
                $columns = $assoc->schema()->columns();
                if (in_array('name', $columns)) {
                    $fields = ['id', 'name'];
                    foreach ($columns as $col) {
                        if ($this->endsWith($col, '_id')) {
                            $fields[] = $col;
                        }
                    }
                    $contain[$assoc->name()] = ['fields' => $fields];
                } elseif (in_array($assoc->name(), ['ModifiedUser', 'CreatedUser'])) {
                    $contain[$assoc->name()] = ['fields' => ['id', 'first_name', 'last_name']];
                } else {
                    $contain[$assoc->name()] = [];
                }
            }
        }
        return $contain;
    }

    public function getSearchKey()
    {
        return $this->Session->read($this->model->alias().'.search.key');
    }

    public function search($model, $order = [])
    {
        $alias = $this->model->alias();
        $controller = $this->controller;
        $request = $this->request;
        $limit = $this->Session->check($alias.'.search.limit') ? $this->Session->read($alias.'.search.limit') : key($this->pageOptions);
        $search = $this->Session->check($alias.'.search.key') ? $this->Session->read($alias.'.search.key') : '';
        $schema = $this->getSchema($model);

        if ($request->is(['post', 'put'])) {
            if (isset($request->data['Search'])) {
                if (array_key_exists('searchField', $request->data['Search'])) {
                    $search = trim($request->data['Search']['searchField']);
                }

                if (array_key_exists('limit', $request->data['Search'])) {
                    $limit = $request->data['Search']['limit'];
                    $this->Session->write($alias.'.search.limit', $limit);
                }
            }
        }

        $query = $model->find();

        $options = new ArrayObject([
            'limit' => $this->pageOptions[$limit],
            'auto_contain' => true,
            'auto_search' => true,
            'auto_order' => true
        ]);

        $this->Session->write($alias.'.search.key', $search);
        $this->request->data['Search']['searchField'] = $search;
        $this->request->data['Search']['limit'] = $limit;

        $this->config['search'] = $search;
        $this->config['pageOptions'] = $this->pageOptions;

        $this->debug(__METHOD__, ': Event -> ControllerAction.Controller.beforePaginate');
        $event = new Event('ControllerAction.Controller.beforePaginate', $this, [$this->model, $query, $options]);
        $event = $this->controller->eventManager()->dispatch($event);
        if ($event->isStopped()) {
            return $event->result;
        }

        $this->debug(__METHOD__, ': Event -> ControllerAction.Model.index.beforePaginate');
        $event = new Event('ControllerAction.Model.index.beforePaginate', $this, [$this->request, $query, $options]);
        $event = $this->model->eventManager()->dispatch($event);
        if ($event->isStopped()) {
            return $event->result;
        }

        if ($options['auto_contain']) {
            $contain = $this->getContains($model);
            if (!empty($contain)) {
                $query->contain($contain);
            }
        }

        if ($options['auto_search']) {
            $OR = [];
            if (!empty($search)) {
                foreach ($schema as $name => $obj) {
                    if ($obj['type'] == 'string' && $name != 'password') {
                        $OR[$model->aliasField("$name").' LIKE'] = '%' . $search . '%';
                    }
                }
            }

            if (!empty($OR)) {
                $query->where(['OR' => $OR]);
            }
        }

        if ($options['auto_order']) {
            if (empty($order) && array_key_exists($this->orderField, $schema)) {
                $options['sort'] = 'order';
                $options['direction'] = 'asc';
            }
        }

        unset($options['auto_contain']);
        unset($options['auto_search']);
        unset($options['auto_order']);

        $data = $this->Paginator->paginate($query, $options->getArrayCopy());

        $this->debug(__METHOD__, ': Event -> ControllerAction.Model.index.afterPaginate');
        $event = new Event('ControllerAction.Model.index.afterPaginate', $this, [$data, $query]);
        $event = $this->model->eventManager()->dispatch($event);
        if ($event->isStopped()) {
            return $event->result;
        }
        if (!empty($event->result)) {
            $data = $event->result;
        }

        return $data;
    }

    public function index()
    {
        $model = $this->model;

        $settings = new ArrayObject(['pagination' => true, 'model' => $model->registryAlias()]);
        $query = $model->find();

        $this->debug(__METHOD__, ': Event -> ControllerAction.Model.index.beforeAction');
        $settings['query'] = $query;
        $event = new Event('ControllerAction.Model.index.beforeAction', $this, [$settings]);
        $event = $model->eventManager()->dispatch($event);
        if ($event->isStopped()) {
            return $event->result;
        }
        if ($event->result) {
            $query = $event->result;
        }

        $indexElements = [
            ['name' => 'OpenEmis.ControllerAction/index', 'data' => [], 'options' => []]
        ];

        try {
            if ($settings['pagination']) {
                if ($settings['model'] != $model->registryAlias()) {
                    $model = TableRegistry::get($settings['model']);
                }
                $data = $this->search($model);
                $indexElements[] = ['name' => 'OpenEmis.pagination', 'data' => [], 'options' => []];
            } else {
                $data = $query->all();
            }
        } catch (NotFoundException $e) {
            $this->log($e->getMessage(), 'debug');
            $action = $this->url('index');
            if (array_key_exists('page', $action)) {
                unset($action['page']);
            }
            return $this->controller->redirect($action);
        }

        if ($data instanceof \Cake\Network\Response || ($data instanceof \Cake\ORM\ResultSet && $data->count() == 0)) {
            $this->Alert->info('general.noData');
        }

        $this->debug(__METHOD__, ': Event -> ControllerAction.Model.index.afterAction');
        $event = new Event('ControllerAction.Model.index.afterAction', $this, [$data]);
        $event = $this->model->eventManager()->dispatch($event);
        if (!empty($event->result)) {
            $data = $event->result;
        }
        if ($event->isStopped()) {
            return $event->result;
        }
        $modals = ['delete-modal' => $this->getModalOptions('remove')];
        $this->config['form'] = true;
        $this->config['formButtons'] = false;
        $this->controller->set(compact('data', 'modals', 'indexElements'));
    }

    public function view($id=0)
    {
        $model = $this->model;

        // Event: viewBeforeAction
        $this->debug(__METHOD__, ': Event -> ControllerAction.Model.view.beforeAction');
        $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.view.beforeAction');
        if ($event->isStopped()) {
            return $event->result;
        }
        if ($event->result instanceof Table) {
            $model = $event->result;
        }
        // End Event

        $ids = !empty($id) ? $this->paramsDecode($id) : $id;

        $sessionKey = $model->registryAlias() . '.primaryKey';
        $contain = [];

        foreach ($model->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // only contain belongsTo associations
                $contain[] = $assoc->name();
            }
        }

        if (empty($ids)) {
            if ($this->Session->check($sessionKey)) {
                $ids = $this->Session->read($sessionKey);
            }
        }

        $idKeys = $this->getIdKeys($model, $ids);

        if ($model->exists($idKeys)) {
            $query = $model->find()->where($idKeys)->contain($contain);

            // Event: viewEditBeforeQuery
            $this->debug(__METHOD__, ': Event -> ControllerAction.Model.viewEdit.beforeQuery');
            $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.viewEdit.beforeQuery', null, [$query]);
            if ($event->isStopped()) {
                return $event->result;
            }
            // End Event

            // Event: viewBeforeQuery
            $this->debug(__METHOD__, ': Event -> ControllerAction.Model.view.beforeQuery');
            $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.view.beforeQuery', null, [$query]);
            if ($event->isStopped()) {
                return $event->result;
            }
            // End Event

            $entity = $query->first();

            if (empty($entity)) {
                $this->Alert->warning('general.notExists');
                return $this->controller->redirect($this->url('index'));
            }

            // Event: viewAfterAction
            $this->debug(__METHOD__, ': Event -> ControllerAction.Model.view.afterAction');
            $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.view.afterAction', null, [$entity]);
            if ($event->isStopped()) {
                return $event->result;
            }
            // End Event

            $this->Session->write($sessionKey, $ids);
            $modals = ['delete-modal' => $this->getModalOptions('remove')];
            $this->controller->set('data', $entity);
            $this->controller->set('modals', $modals);
        } else {
            $this->Alert->warning('general.notExists');
            return $this->controller->redirect($this->url('index'));
        }
        $this->config['form'] = false;
    }

    public function add()
    {
        $model = $this->model;
        $request = $this->request;

        // Event: addEditBeforeAction
        $this->debug(__METHOD__, ': Event -> ControllerAction.Model.addEdit.beforeAction');
        $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.addEdit.beforeAction');
        if ($event->isStopped()) {
            return $event->result;
        }
        if ($event->result instanceof Table) {
            $model = $event->result;
        }
        // End Event

        // Event: addBeforeAction
        $this->debug(__METHOD__, ': Event -> ControllerAction.Model.add.beforeAction');
        $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.add.beforeAction');
        if ($event->isStopped()) {
            return $event->result;
        }
        if ($event->result instanceof Table) {
            $model = $event->result;
        }
        // End Event

        $entity = $model->newEntity();

        if ($request->is(['get'])) {
            // Event: addOnInitialize
            $this->debug(__METHOD__, ': Event -> ControllerAction.Model.add.onInitialize');
            $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.add.onInitialize', null, [$entity]);
            if ($event->isStopped()) {
                return $event->result;
            }
            // End Event
        } elseif ($request->is(['post', 'put'])) {
            $submit = isset($request->data['submit']) ? $request->data['submit'] : 'save';
            $patchOptions = new ArrayObject([]);
            $requestData = new ArrayObject($request->data);

            $params = [$entity, $requestData, $patchOptions];

            if ($submit == 'save') {
                // Event: addEditBeforePatch
                $this->debug(__METHOD__, ': Event -> ControllerAction.Model.addEdit.beforePatch');
                $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.addEdit.beforePatch', null, $params);
                if ($event->isStopped()) {
                    return $event->result;
                }
                // End Event

                // Event: addBeforePatch
                $this->debug(__METHOD__, ': Event -> ControllerAction.Model.add.beforePatch');
                $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.add.beforePatch', null, $params);
                if ($event->isStopped()) {
                    return $event->result;
                }
                // End Event

                $patchOptionsArray = $patchOptions->getArrayCopy();
                $request->data = $requestData->getArrayCopy();
                $entity = $model->patchEntity($entity, $request->data, $patchOptionsArray);

                // Event: addAfterPatch
                $this->debug(__METHOD__, ': Event -> ControllerAction.Model.add.afterPatch');
                $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.add.afterPatch', null, $params);
                if ($event->isStopped()) {
                    return $event->result;
                }
                // End Event
                $request->data = $requestData->getArrayCopy();

                $process = function ($model, $entity) {
                    return $model->save($entity);
                };

                // Event: onBeforeSave
                $this->debug(__METHOD__, ': Event -> ControllerAction.Model.add.beforeSave');
                $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.add.beforeSave', null, [$entity, $requestData]);
                if ($event->isStopped()) {
                    return $event->result;
                }
                if (is_callable($event->result)) {
                    $process = $event->result;
                }
                // End Event

                if ($process($model, $entity)) {
                    $this->Alert->success('general.add.success');
                    // Event: addAfterSave
                    $this->debug(__METHOD__, ': Event -> ControllerAction.Model.add.afterSave');
                    $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.add.afterSave', null, [$entity, $requestData]);
                    if ($event->isStopped()) {
                        return $event->result;
                    }
                    // End Event

                    return $this->controller->redirect($this->url('index'));
                } else {
                    $this->log($entity->errors(), 'debug');
                    $this->Alert->error('general.add.failed');
                }
            } else {
                $patchOptions['validate'] = false;
                $methodKey = 'on' . ucfirst($submit);

                // Event: addEditOnReload
                $eventKey = 'ControllerAction.Model.addEdit.' . $methodKey;
                $this->debug(__METHOD__, ': Event -> ' . $eventKey);
                $method = 'addEdit' . ucfirst($methodKey);
                $event = $this->dispatchEvent($this->model, $eventKey, $method, $params);
                if ($event->isStopped()) {
                    return $event->result;
                }
                // End Event

                // Event: addOnReload
                $eventKey = 'ControllerAction.Model.add.' . $methodKey;
                $this->debug(__METHOD__, ': Event -> ' . $eventKey);
                $method = 'add' . ucfirst($methodKey);
                $event = $this->dispatchEvent($this->model, $eventKey, $method, $params);
                if ($event->isStopped()) {
                    return $event->result;
                }
                // End Event

                $patchOptionsArray = $patchOptions->getArrayCopy();
                $request->data = $requestData->getArrayCopy();
                $entity = $model->patchEntity($entity, $request->data, $patchOptionsArray);
            }
        }

        // Event: addEditAfterAction
        $this->debug(__METHOD__, ': Event -> ControllerAction.Model.addEdit.afterAction');
        $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.addEdit.afterAction', null, [$entity]);
        if ($event->isStopped()) {
            return $event->result;
        }
        // End Event

        // Event: addAfterAction
        $this->debug(__METHOD__, ': Event -> ControllerAction.Model.add.afterAction');
        $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.add.afterAction', null, [$entity]);
        if ($event->isStopped()) {
            return $event->result;
        }
        // End Event
        $this->config['form'] = true;
        $this->controller->set('data', $entity);
    }

    public function edit($id=0)
    {
        $model = $this->model;
        $request = $this->request;

        // Event: addEditBeforeAction
        $this->debug(__METHOD__, ': Event -> ControllerAction.Model.addEdit.beforeAction');
        $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.addEdit.beforeAction');
        if ($event->isStopped()) {
            return $event->result;
        }
        if ($event->result instanceof Table) {
            $model = $event->result;
        }
        // End Event

        // Event: editBeforeAction
        $this->debug(__METHOD__, ': Event -> ControllerAction.Model.edit.beforeAction');
        $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.edit.beforeAction');
        if ($event->isStopped()) {
            return $event->result;
        }
        if ($event->result instanceof Table) {
            $model = $event->result;
        }
        // End Event

        $ids = $this->paramsDecode($id);
        $idKeys = $this->getIdKeys($model, $ids);

        if ($model->exists($idKeys)) {
            $query = $model->find()->where($idKeys);

            // Event: viewEditBeforeQuery
            $this->debug(__METHOD__, ': Event -> ControllerAction.Model.viewEdit.beforeQuery');
            $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.viewEdit.beforeQuery', null, [$query]);
            if ($event->isStopped()) {
                return $event->result;
            }
            // End Event

            // Event: editBeforeQuery
            $this->debug(__METHOD__, ': Event -> ControllerAction.Model.edit.beforeQuery');
            $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.edit.beforeQuery', null, [$query]);
            if ($event->isStopped()) {
                return $event->result;
            }
            // End Event

            $entity = $query->first();

            // Event: editAfterQuery
            $this->debug(__METHOD__, ': Event -> ControllerAction.Model.edit.afterQuery');
            $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.edit.afterQuery', null, [$entity]);
            if ($event->isStopped()) {
                return $event->result;
            }
            // End Event

            if (empty($entity)) {
                $this->Alert->warning('general.notExists');
                return $this->controller->redirect($this->url('index'));
            }

            if ($this->request->is(['get'])) {
                // Event: editOnInitialize
                $this->debug(__METHOD__, ': Event -> ControllerAction.Model.edit.onInitialize');
                $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.edit.onInitialize', null, [$entity]);
                if ($event->isStopped()) {
                    return $event->result;
                }
                // End Event
            } elseif ($this->request->is(['post', 'put'])) {
                $submit = isset($this->request->data['submit']) ? $this->request->data['submit'] : 'save';
                $patchOptions = new ArrayObject([]);
                $requestData = new ArrayObject($request->data);

                $params = [$entity, $requestData, $patchOptions];

                if ($submit == 'save') {
                    // Event: addEditBeforePatch
                    $this->debug(__METHOD__, ': Event -> ControllerAction.Model.addEdit.beforePatch');
                    $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.addEdit.beforePatch', null, $params);
                    if ($event->isStopped()) {
                        return $event->result;
                    }
                    // End Event

                    // Event: editBeforePatch
                    $this->debug(__METHOD__, ': Event -> ControllerAction.Model.edit.beforePatch');
                    $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.edit.beforePatch', null, $params);
                    if ($event->isStopped()) {
                        return $event->result;
                    }
                    // End Event

                    $patchOptionsArray = $patchOptions->getArrayCopy();
                    $request->data = $requestData->getArrayCopy();
                    $entity = $model->patchEntity($entity, $request->data, $patchOptionsArray);

                    $process = function ($model, $entity) {
                        return $model->save($entity);
                    };

                    // Event: onBeforeSave
                    $this->debug(__METHOD__, ': Event -> ControllerAction.Model.edit.beforeSave');
                    $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.edit.beforeSave', null, [$entity, $requestData]);
                    if ($event->isStopped()) {
                        return $event->result;
                    }
                    if (is_callable($event->result)) {
                        $process = $event->result;
                    }
                    // End Event

                    if ($process($model, $entity)) {
                        // event: onSaveSuccess
                        $this->Alert->success('general.edit.success');

                        // Event: editAfterSave
                        $this->debug(__METHOD__, ': Event -> ControllerAction.Model.edit.afterSave');
                        $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.edit.afterSave', null, $params);
                        if ($event->isStopped()) {
                            return $event->result;
                        }
                        // End Event

                        return $this->controller->redirect($this->url('view'));
                    } else {
                        // event: onSaveFailed
                        $this->log($entity->errors(), 'debug');
                        $this->Alert->error('general.edit.failed');
                    }
                } else {
                    $patchOptions['validate'] = false;
                    $methodKey = 'on' . ucfirst($submit);

                    // Event: addEditOnReload
                    $eventKey = 'ControllerAction.Model.addEdit.' . $methodKey;
                    $this->debug(__METHOD__, ': Event -> ' . $eventKey);
                    $method = 'addEdit' . ucfirst($methodKey);
                    $event = $this->dispatchEvent($this->model, $eventKey, $method, $params);
                    if ($event->isStopped()) {
                        return $event->result;
                    }
                    // End Event

                    // Event: editOnReload
                    $eventKey = 'ControllerAction.Model.edit.' . $methodKey;
                    $this->debug(__METHOD__, ': Event -> ' . $eventKey);
                    $method = 'edit' . ucfirst($methodKey);
                    $event = $this->dispatchEvent($this->model, $eventKey, $method, $params);
                    if ($event->isStopped()) {
                        return $event->result;
                    }
                    // End Event

                    $patchOptionsArray = $patchOptions->getArrayCopy();
                    $request->data = $requestData->getArrayCopy();
                    $entity = $model->patchEntity($entity, $request->data, $patchOptionsArray);
                }
            }

            // Event: addEditAfterAction
            $this->debug(__METHOD__, ': Event -> ControllerAction.Model.addEdit.afterAction');
            $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.addEdit.afterAction', null, [$entity]);
            if ($event->isStopped()) {
                return $event->result;
            }
            // End Event

            // Event: editAfterAction
            $this->debug(__METHOD__, ': Event -> ControllerAction.Model.edit.afterAction');
            $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.edit.afterAction', null, [$entity]);
            if ($event->isStopped()) {
                return $event->result;
            }
            // End Event

            $this->controller->set('data', $entity);
        } else {
            $this->Alert->warning('general.notExists');
            return $this->controller->redirect($this->url('index'));
        }
        $this->config['form'] = true;
    }

    public function remove($id = 0)
    {
        $ids = ($id) ? $this->paramsDecode($id) : 0;
        $request = $this->request;
        $model = $this->model;
        $settings = new ArrayObject([]);

        // Event: deleteBeforeAction
        $this->debug(__METHOD__, ': Event -> ControllerAction.Model.delete.beforeAction');
        $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.delete.beforeAction', null, [$settings]);
        if ($event->isStopped()) {
            return $event->result;
        }
        if ($settings->offsetExists('model')) {
            if ($settings['model'] instanceof Table) {
                $model = $settings['model'];
            } else {
                $model = TableRegistry::get($settings['model']);
            }
        }
        if ($settings->offsetExists('deleteStrategy')) {
            $this->deleteStrategy = $settings['deleteStrategy'];
        }
        // End Event

        $primaryKey = $model->primaryKey();

        if ($request->is('get')) {
            $idKeys = $this->getIdKeys($model, $ids);
            if ($model->exists($idKeys)) {
                $entity = $model->get($idKeys);

                $query = $model->find();
                $extra = new ArrayObject([]);

                $label = [
                    'nameLabel' => 'Convert From',
                    'tableLabel' => 'Apply To'
                ];
                if ($this->deleteStrategy == 'transfer') {
                    $label['nameLabel'] = __('Convert From');
                    $label['tableLabel'] = __('Apply To');
                } elseif ($this->deleteStrategy == 'restrict') {
                    $label['nameLabel'] = __('To Be Deleted');
                    $label['tableLabel'] = __('Associated Records');
                }

                $extra['keyField'] = $model->primaryKey();
                $extra['valueField'] = 'name';

                // Event: deleteOnInitialize
                $this->debug(__METHOD__, ': Event -> ControllerAction.Model.delete.onInitialize');
                $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.delete.onInitialize', null, [$entity, $query, $extra]);
                if ($event->isStopped()) {
                    return $event->result;
                }
                // End Event

                if ($this->deleteStrategy != 'restrict') {
                    $notIdKeys = $idKeys;
                    foreach ($notIdKeys as $key => $value) {
                        $notIdKeys[$key.' <>'] = $value;
                        unset($notIdKeys[$key]);
                    }
                    $query->find('all')->where($notIdKeys);

                    // Event: deleteUpdateCovertOptions
                    $this->debug(__METHOD__, ': Event -> ControllerAction.Model.onGetConvertOptions');
                    $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.onGetConvertOptions', null, [$entity, $query]);
                    if ($event->isStopped()) {
                        return $event->result;
                    }

                    $convertOptionResults = $query->toArray();

                    $convertOptions = [];
                    foreach ($convertOptionResults as $key => $value) {
                        $ids = ['id' => $value->id];

                        $keysToEncode = $model->getIdKeys($model, $ids, false);
                        $encodedKey = $model->paramsEncode($keysToEncode);
                        $convertOptions[$encodedKey] = $value->{$extra['valueField']};
                    }

                    if (empty($convertOptions)) {
                        $convertOptions[''] = __('No Available Options');
                    }
                    $this->controller->set('convertOptions', $convertOptions);
                }

                $totalCount = 0;
                $associations = [];
                foreach ($model->associations() as $assoc) {
                    if (!$assoc->dependent() || $this->deleteStrategy == 'restrict') {
                        if ($assoc->type() == 'oneToMany' || $assoc->type() == 'manyToMany') {
                            $excludedModels = [];
                            if ($extra->offsetExists('excludedModels')) {
                                $excludedModels = $extra['excludedModels'];
                            }
                            if (!array_key_exists($assoc->alias(), $associations) && !in_array($assoc->alias(), $excludedModels)) {
                                $count = 0;
                                $modelAssociationTable = $assoc;
                                if ($assoc->type() == 'manyToMany') {
                                    $modelAssociationTable = $assoc->junction();
                                }
                                $bindingKey = $assoc->bindingKey();
                                $foreignKey = $assoc->foreignKey();
                                $conditions = [];
                                if (is_array($foreignKey)) {
                                    foreach ($foreignKey as $index => $key) {
                                        $conditions[$modelAssociationTable->aliasField($key)] = $ids[$bindingKey[$index]];
                                    }
                                } else {
                                    $conditions[$modelAssociationTable->aliasField($foreignKey)] = $ids[$bindingKey];
                                }

                                $count = $modelAssociationTable->find()
                                    ->where($conditions)
                                    ->count();
                                $title = $this->Alert->getMessage($assoc->aliasField('title'));
                                if ($title == '[Message Not Found]') {
                                    $title = $assoc->name();
                                }
                                $title = Inflector::humanize(Inflector::underscore($title));
                                $associations[$assoc->alias()] = ['model' => __($title), 'count' => $count];
                                $totalCount += $count;
                            }
                        }
                    }
                }
                if ($extra->offsetExists('associatedRecords')) {
                    foreach ($extra['associatedRecords'] as $key => $record) {
                        $title = Inflector::humanize(Inflector::underscore($record['model']));
                        $extra['associatedRecords'][$key]['model'] = $title;
                        $totalCount += $record['count'];
                    }
                    $associations = array_merge($associations, $extra['associatedRecords']);
                }
                $showFormButton = true;
                if ($this->deleteStrategy == 'restrict' && $totalCount > 0) {
                    $showFormButton = false;
                    $this->Alert->error('general.delete.restrictDeleteBecauseAssociation');
                }
                $this->controller->set('label', $label);
                $this->controller->set(compact('showFormButton'));
                $this->controller->set('deleteStrategy', $this->deleteStrategy);
                $this->controller->set('data', $entity);
                if (!is_array($primaryKey)) {
                    $primaryKey = [$primaryKey];
                }
                $this->controller->set('primaryKey', $primaryKey);
                $this->controller->set('associations', $associations);
            } else {
                $this->Alert->warning('general.notExists');
                return $this->controller->redirect($this->url('index', 'QUERY'));
            }
        } elseif ($request->is('delete')) {
            $this->autoRender = false;

            if ($this->deleteStrategy == 'restrict' || $this->deleteStrategy == 'transfer') {
                $primaryKeyArr = [];
                if (!is_array($primaryKey)) {
                    $primaryKeyArr[] = $primaryKey;
                } else {
                    $primaryKeyArr = $primaryKey;
                }
                $primaryKeyValue = array_intersect_key($request->data, array_flip($primaryKeyArr));
                $ids = $this->getIdKeys($model, $primaryKeyValue, false);
            } else {
                $id = $this->paramsDecode($request->data('primaryKey'));
                $ids = $this->getIdKeys($model, $id, false);
            }

            $deleteOptions = new ArrayObject([]);
            $extra = new ArrayObject(['excludedModels' => []]);

            $process = function ($model, $ids, $deleteOptions) {
                $primaryKey = $model->primaryKey();
                $idKeys = [];
                if (is_array($primaryKey)) {
                    foreach ($primaryKey as $key) {
                        $idKeys[$model->aliasField($key)] = $ids[$key];
                    }
                } else {
                    $idKeys[$model->aliasField($primaryKey)] = $ids[$primaryKey];
                }

                if ($model->exists($idKeys)) {
                    $entity = $model->get($ids);
                    return $model->delete($entity, $deleteOptions->getArrayCopy());
                } else {
                    // If id(to be deleted) cannot be found, return a successful deletion message
                    return true;
                }
            };

            // Event: onBeforeDelete
            $params = [$deleteOptions, $ids, $extra];
            $this->debug(__METHOD__, ': Event -> ControllerAction.Model.onBeforeDelete');
            $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.onBeforeDelete', null, $params);
            if ($event->isStopped()) {
                return $event->result;
            }
            if (is_callable($event->result)) {
                $process = $event->result;
            }
            // End Event
            if ($this->deleteStrategy == 'cascade' || $this->deleteStrategy == 'restrict') {
                if ($process($model, $ids, $deleteOptions)) {
                    $this->Alert->success('general.delete.success');
                } else {
                    $this->Alert->error('general.delete.failed');
                }
                return $this->controller->redirect($this->url('index', 'QUERY'));
            } else {
                $transferFrom = $this->getIdKeys($model, $request->data, false);
                $transferTo = $this->paramsDecode($this->request->data('transfer_to'));

                // Checking of association for delete transfer, if the association count is 0,
                // it means that no record is associated with it and it is safe to delete the record
                $totalCount = 0;

                if (empty($transferTo)) {
                    $associations = [];
                    foreach ($model->associations() as $assoc) {
                        // if dependent is false then it will count the associations
                        if (!$assoc->dependent() && ($assoc->type() == 'oneToMany' || $assoc->type() == 'manyToMany')) {
                            if (!array_key_exists($assoc->alias(), $associations)) {
                                $count = 0;
                                if ($assoc->type() == 'oneToMany') {
                                    $count = $assoc->find()
                                    ->where([$assoc->aliasField($assoc->foreignKey()) => $transferFrom])
                                    ->count();
                                    $totalCount = $totalCount + $count;
                                } else {
                                    $modelAssociationTable = $assoc->junction();
                                    $count += $modelAssociationTable->find()
                                        ->where([$modelAssociationTable->aliasField($assoc->foreignKey()) => $transferFrom])
                                        ->count();
                                    $totalCount = $totalCount + $count;
                                }
                                $associations[$assoc->alias()] = $assoc->table();
                            }
                        }
                    }
                }
                if ($totalCount > 0) {
                    $this->Alert->error('general.deleteTransfer.restrictDelete');
                    return $this->controller->redirect($this->url('remove'));
                } else {
                    $associations = [];
                    foreach ($model->associations() as $assoc) {
                        if ($assoc->type() == 'oneToMany' || $assoc->type() == 'manyToMany') {
                            if (!array_key_exists($assoc->alias(), $associations)) {
                                // $assoc->dependent(false);
                                $associations[$assoc->alias()] = $assoc;
                            }
                        }
                    }

                    if ($process($model, $transferFrom, $deleteOptions)) {
                        $ids = $this->getIdKeys($model, $request->data, false);
                        $transferOptions = new ArrayObject([]);

                        $transferProcess = function ($associations, $transferFrom, $transferTo, $model) {
                            foreach ($associations as $assoc) {
                                if ($assoc->type() == 'oneToMany') {
                                    $bindingKey = $assoc->bindingKey();
                                    $foreignKey = $assoc->foreignKey();

                                    $fromConditions = [];

                                    if (is_array($foreignKey)) {
                                        foreach ($foreignKey as $index => $key) {
                                            $fromConditions[$key] = $transferFrom[$bindingKey[$index]];
                                        }
                                    } else {
                                        $fromConditions[$foreignKey] = $transferFrom[$bindingKey];
                                    }

                                    $toConditions = [];

                                    if (is_array($foreignKey)) {
                                        foreach ($foreignKey as $index => $key) {
                                            $toConditions[$key] = $transferTo[$bindingKey[$index]];
                                        }
                                    } else {
                                        $toConditions[$foreignKey] = $transferTo[$bindingKey];
                                    }

                                    $assoc->updateAll(
                                        [$toConditions],
                                        [$fromConditions]
                                    );
                                } elseif ($assoc->type() == 'manyToMany') {
                                    $modelAssociationTable = $assoc->junction();

                                    $bindingKey = $association->bindingKey();
                                    $foreignKey = $association->foreignKey();

                                    $toConditions = [];

                                    if (is_array($foreignKey)) {
                                        foreach ($foreignKey as $index => $key) {
                                            $toConditions[$key] = $transferTo[$bindingKey[$index]];
                                        }
                                    } else {
                                        $toConditions[$foreignKey] = $transferTo[$bindingKey];
                                    }

                                    $fromConditions = [];

                                    if (is_array($foreignKey)) {
                                        foreach ($foreignKey as $index => $key) {
                                            $fromConditions[$key] = $transferFrom[$bindingKey[$index]];
                                        }
                                    } else {
                                        $fromConditions[$foreignKey] = $transferFrom[$bindingKey];
                                    }

                                    $targetForeignKey = $association->targetForeignKey();

                                    // List of the target foreign keys for subqueries
                                    $targetForeignKeys = $modelAssociationTable->find()
                                        ->select($targetForeignKey)
                                        ->where($toConditions);

                                    $notUpdateQuery = $modelAssociationTable->query()
                                        ->select($targetForeignKey)
                                        ->from(['TargetTable' => $targetForeignKeys]);

                                    if (!empty($notUpdateQuery)) {
                                        $condition = [];

                                        $targetForeignKeyString = '';
                                        if (is_array($targetForeignKey)) {
                                            $targetForeignKeyString = '('. impode(', ', $targetForeignKey) . ')';
                                        } else {
                                            $targetForeignKeyString = $targetForeignKey;
                                        }

                                        $notCondition = $fromConditions;
                                        $notCondition[$association->targetForeignKey().' IN '] = $notUpdateQuery;

                                        $condition = [
                                            $fromConditions,
                                            'NOT' => $notCondition
                                        ];

                                        // Update all transfer records
                                        $modelAssociationTable->updateAll(
                                            $toConditions,
                                            $condition
                                        );

                                        // Delete orphan records
                                        $modelAssociationTable->deleteAll(
                                            $fromConditions
                                        );
                                    }
                                }
                            }
                        };

                        // Event: onDeleteTransfer
                        $params = [$transferOptions, $ids];
                        $this->debug(__METHOD__, ': Event -> ControllerAction.Model.onDeleteTransfer');
                        $event = $this->dispatchEvent($this->model, 'ControllerAction.Model.onDeleteTransfer', null, $params);
                        if ($event->isStopped()) {
                            return $event->result;
                        }
                        if (is_callable($event->result)) {
                            $transferProcess = $event->result;
                        }

                        $transferProcess($associations, $transferFrom, $transferTo, $model);
                        $this->Alert->success('general.delete.success');
                    } else {
                        $this->Alert->error('general.delete.failed');
                    }
                    return $this->controller->redirect($this->url('index', 'QUERY'));
                }
            }
        } else {
            $this->Alert->error('general.delete.failed');
            return $this->controller->redirect($this->url('index', 'QUERY'));
        }
    }

    public function download($id)
    {
        $ids = $this->paramsDecode($id);
        $fileUpload = $this->model->behaviors()->get('FileUpload');
        $name = '';
        if (!empty($fileUpload)) {
            $name = $fileUpload->config('name');
            $content = $fileUpload->config('content');
        }

        $data = $this->model->get($ids);
        $fileName = $data->{$name};
        $pathInfo = pathinfo($fileName);

        $file = $fileUpload->getActualFile($data->{$content});
        $fileType = $fileUpload->getFileType($pathInfo['extension']);
        if (!$fileType) {
            $fileType = 'image/jpg';
        }
        // echo '<img src="data:image/jpg;base64,' .   base64_encode($file)  . '" />';

        header("Pragma: public", true);
        header("Expires: 0"); // set expiration time
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: " . $fileType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        echo $file;
        exit();
    }

    public function reorder()
    {
        $this->autoRender = false;
        $this->controller->autoRender=false;
        $request = $this->request;

        if ($request->is('ajax')) {
            $model = $this->model;
            $primaryKey = $model->primaryKey();
            $orderField = $this->orderField;

            $encodedIds = json_decode($request->data("ids"));

            $ids = [];
            $idKeys = [];

            foreach ($encodedIds as $id) {
                $ids[] = $this->paramsDecode($id);
                $idKeys[] = $model->getIdKeys($model, $this->paramsDecode($id));
            }

            if (!empty($ids)) {
                $originalOrder = $model
                    ->find()
                    ->select($primaryKey)
                    ->select($orderField)
                    ->where(['OR' => $idKeys])
                    ->order([$model->aliasField($orderField)])
                    ->hydrate(false)
                    ->toArray();

                $originalOrder = array_reverse($originalOrder);

                foreach ($ids as $id) {
                    $orderValue = array_pop($originalOrder);
                    $model->updateAll([$orderField => $orderValue[$orderField]], [$id]);
                }
            }
        }
    }

    // NOT IN USED
    // public function fixOrder($conditions) {
    //  $model = $this->model;
    //  $count = $model->find('count', array('conditions' => $conditions));
    //  if($count > 0) {
    //      $list = $model->find('list', array(
    //          'conditions' => $conditions,
    //          'order' => array(
    //              $model->alias().'.'.$this->orderField,
    //              $model->alias().'.'.$model->primaryKey()
    //          )
    //      ));
    //      $order = 1;
    //      foreach($list as $id => $name) {
    //          $model->id = $id;
    //          $model->saveField($this->orderField, $order++);
    //      }
    //  }
    // }

    public function getPlugin($model)
    {
        $array = $this->getModel($model);
        return $array['plugin'];
    }

    public function getModel($model)
    {
        $split = explode('.', $model);
        $plugin = null;
        $modelClass = $model;
        if (count($split) > 1) {
            $plugin = $split[0];
            $modelClass = $split[1];
        }
        return ['plugin' => $plugin, 'model' => $modelClass];
    }

    public function getSchema($model)
    {
        $schema = $model->schema();
        $columns = $schema->columns();
        $fields = [];
        foreach ($columns as $col) {
            $fields[$col] = $schema->column($col);
        }
        return $fields;
    }

    public function addField($field, $attr=[])
    {
        $this->field($field, $attr);
    }

    public function field($field, $attr=[])
    {
        $model = $this->model;
        $className = $model->alias();

        if (!isset($model->fieldOrder)) {
            $model->fieldOrder = 0;
        }
        $model->fieldOrder = $model->fieldOrder + 1;

        $order = false;
        if (array_key_exists('after', $attr)) {
            $after = $attr['after'];
            $order = $this->getOrderValue($model, $after, 'after');
        } elseif (array_key_exists('before', $attr)) {
            $before = $attr['before'];
            $order = $this->getOrderValue($model, $before, 'before');
        }

        if (!empty($this->plugin)) {
            $className = $this->plugin . '.' . $className;
        }

        $_attr = [
            'type' => 'string',
            'null' => true,
            'autoIncrement' => false,
            'order' => $order ? $order : $model->fieldOrder,
            'visible' => true,
            'field' => $field,
            'model' => $model->alias(),
            'className' => $className
        ];

        if (array_key_exists($field, $model->fields)) {
            $_attr = array_merge($_attr, $model->fields[$field]);
        }

        $attr = array_merge($_attr, $attr);
        $model->fields[$field] = $attr;

        $method = 'onUpdateField' . Inflector::camelize($field);
        $eventKey = 'ControllerAction.Model.' . $method;
        $params = [$attr, $this->currentAction, $this->request];
        $this->debug(__METHOD__, ': Event -> ' . $eventKey);
        $event = $this->dispatchEvent($this->model, $eventKey, $method, $params);
        if (is_array($event->result)) {
            $model->fields[$field] = $event->result;
        }

        return $model->fields[$field];
    }

    public function getFields($model)
    {
        $ignoreFields = $this->ignoreFields;
        $className = $model->alias();
        if (!empty($this->plugin)) {
            $className = $this->plugin . '.' . $className;
        }
        $fields = $this->getSchema($model);
        $visibility = ['view' => true, 'edit' => true, 'index' => true];

        $i = 50;
        foreach ($fields as $key => $obj) {
            $fields[$key]['order'] = $i++;
            $fields[$key]['visible'] = $visibility;
            $fields[$key]['field'] = $key;
            $fields[$key]['model'] = $model->alias();
            $fields[$key]['className'] = $className;

            if ($key == 'password') {
                $fields[$key]['visible'] = false;
            }
            /*
            if ($obj['type'] == 'binary') {
                $fields[$key]['visible']['index'] = false;
            }
            */
        }

        if (is_array($model->primaryKey())) {
            if (array_key_exists('id', $fields)) {
                $fields['id']['type'] = 'hidden';
            }
            foreach ($model->primaryKey() as $value) {
                $fields[$value]['type'] = 'hidden';
            }
        } else {
            $fields[$model->primaryKey()]['type'] = 'hidden';
        }

        foreach ($ignoreFields as $field) {
            if (array_key_exists($field, $fields)) {
                $fields[$field]['visible']['index'] = false;
                $fields[$field]['visible']['view'] = true;
                $fields[$field]['visible']['edit'] = false;
                $fields[$field]['labelKey'] = 'general';
            }
        }
        $model->fields = $fields;
        return $fields;
    }

    public function setFieldOrder($field, $order=0)
    {
        $fields = $this->model->fields;

        if (is_array($field)) {
            foreach ($field as $key) {
                $fields[$key]['order'] = $order++;
            }
        } else {
            $found = false;
            $count = 0;
            foreach ($fields as $key => $obj) {
                $count++;
                if (!isset($fields[$key]['order'])) {
                    $fields[$key]['order'] = $count;
                }

                if ($found && $key !== $field) {
                    $fields[$key]['order'] = $fields[$key]['order'] + 1;
                } else {
                    if ($field === $key) {
                        $found = true;
                        $fields[$key]['order'] = $order;
                    } elseif ($fields[$key]['order'] == $order) {
                        $found = true;
                        $fields[$key]['order'] = $order + 1;
                    }
                }
            }
            $fields[$field]['order'] = $order;
            uasort($fields, [$this, 'sortFields']);
        }
        $this->model->fields = $fields;
    }

    public function setFieldVisible($actions, $fields)
    {
        foreach ($this->model->fields as $key => $attr) {
            if (in_array($key, $fields)) {
                foreach ($actions as $action) {
                    $this->model->fields[$key]['visible'][$action] = true;
                }
            } else {
                $this->model->fields[$key]['visible'] = false;
            }
        }
    }

    public static function sortFields($a, $b)
    {
        if (isset($a['order']) && isset($b['order'])) {
            return $a['order'] >= $b['order'];
        } else {
            return true;
        }
    }

    public function dispatchEvent($subject, $eventKey, $method=null, $params=[])
    {
        $eventMap = $subject->implementedEvents();
        $event = new Event($eventKey, $this, $params);

        if (!array_key_exists($eventKey, $eventMap) && !is_null($method)) {
            if (method_exists($subject, $method) || $subject->behaviors()->hasMethod($method)) {
                $subject->eventManager()->on($eventKey, [], [$subject, $method]);
            }
        }
        return $subject->eventManager()->dispatch($event);
    }

    public function endsWith($haystack, $needle)
    {
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

    public function debug($method, $message='')
    {
        if ($this->debug) {
            $pos = strrpos($method, "\\");
            if ($pos !== false) {
                $pos++;
                $method = substr($method, $pos);
            }
            Log::write('debug', $method . $message);
        }
    }

    public function getTriggerFrom()
    {
        return $this->triggerFrom;
    }

    private function getOrderValue($model, $field, $insert)
    {
        if (!array_key_exists($field, $model->fields)) {
            Log::write('debug', 'Attempted to add ' . $insert . ' invalid field: ' . $field);
            return false;
        }
        $order = 0;

        uasort($model->fields, [$this, '_sortByOrder']);

        if ($insert == 'before') {
            foreach ($model->fields as $key => $attr) {
                if ($key == $field) {
                    $order = $attr['order'] - 1;
                    break;
                }
                $model->fields[$key]['order'] = $attr['order'] - 1;
            }
        } elseif ($insert == 'after') {
            $start = false;
            foreach ($model->fields as $key => $attr) {
                if ($start) {
                    $model->fields[$key]['order'] = $attr['order'] + 1;
                }
                if ($key == $field) {
                    $start = true;
                    $order = $attr['order'] + 1;
                }
            }
        }
        return $order;
    }

    public function getAssociatedRecords($model, $entity, $extra)
    {
        $dependent = [true, false];
        if ($extra->offsetExists('deleteStrategy')) {
            switch ($extra['deleteStrategy']) {
                case 'restrict':
                    $dependent = [true, false];
                    break;
                case 'transfer':
                    $dependent = [false];
                    break;
            }
        }
        $primaryKey = $model->primaryKey();
        $id = $entity->{$primaryKey};
        $associations = [];
        foreach ($model->associations() as $assoc) {
            if (in_array($assoc->dependent(), $dependent)) {
                if ($assoc->type() == 'oneToMany' || $assoc->type() == 'manyToMany') {
                    if (!array_key_exists($assoc->alias(), $associations)) {
                        $count = 0;
                        if ($assoc->type() == 'oneToMany') {
                            $count = $assoc->find()
                            ->where([$assoc->aliasField($assoc->foreignKey()) => $id])
                            ->count();
                        } else {
                            $modelAssociationTable = $assoc->junction();
                            $count = $modelAssociationTable->find()
                                ->where([$modelAssociationTable->aliasField($assoc->foreignKey()) => $id])
                                ->count();
                        }
                        $title = $assoc->name();
                        $event = $assoc->dispatchEvent('ControllerAction.Model.transfer.getModelTitle', [], $this);
                        if (!is_null($event->result)) {
                            $title = $event->result;
                        }

                        $isAssociated = true;
                        if ($extra->offsetExists('excludedModels')) {
                            if (in_array($title, $extra['excludedModels'])) {
                                $isAssociated = false;
                            }
                        }
                        if ($isAssociated) {
                            $associations[$assoc->alias()] = ['model' => $title, 'count' => $count];
                        }
                    }
                }
            }
        }
        return $associations;
    }

    public function hasAssociatedRecords($model, $entity, $extra)
    {
        $records = $this->getAssociatedRecords($model, $entity, $extra);
        $found = false;
        foreach ($records as $count) {
            if ($count['count'] > 0) {
                $found = true;
                break;
            }
        }
        return $found;
    }
}
