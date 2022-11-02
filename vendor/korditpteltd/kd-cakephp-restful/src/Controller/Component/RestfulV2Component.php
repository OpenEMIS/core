<?php
namespace Restful\Controller\Component;

use ArrayObject;
use Exception;

use Cake\Core\Configure;
use Cake\Chronos\MutableDate;
use Cake\Chronos\Chronos;
use Cake\Chronos\Date;
use Cake\Chronos\MutableDateTime;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;
use Cake\Utility\Hash;

use Restful\Model\Table\RestfulAppTable;
use Restful\Controller\RestfulInterface;

class RestfulV2Component extends Component implements RestfulInterface
{
    private $model = null;
    private $controller = null;
    private $extra = null;
    private $serialize = null;

    public $components = ['Auth'];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->controller = $this->_registry->getController();
        $this->extra = new ArrayObject([]);
        $this->serialize = new ArrayObject([]);
    }

    /******************************************************************************************************************
    **
    ** Events
    **
    ******************************************************************************************************************/

    // Is called after the controller's beforeFilter method but before the controller executes the current action handler.
    public function startup(Event $event)
    {
        $controller = $this->controller;
        $request = $this->request;

        if (empty($request->params['_ext'])) {
            $request->params['_ext'] = 'json';
        }

        if (isset($request->model)) {
            $tableAlias = $request->model;
            $model = $this->instantiateModel($tableAlias);

            if ($model != false) {
                $this->model = $model;
                // Event to get allowed action and allowed table to be accessible via restful
                $event = $model->dispatchEvent('Restful.Model.onGetAllowedActions', null, $this);
                if (is_array($event->result)) {
                    $this->Auth->allow($event->result);
                }

                // initial processing of request queries
                $user = $controller->getAuthorizedUser();
                $this->extra['user'] = $user;
                $this->initRequestQueries($model);
            }
        }
    }

    // Is called after the controller executes the requested action’s logic, but before the controller renders views and layout.
    public function beforeRender(Event $event)
    {
        $controller = $this->controller;
        $serialize = $this->serialize;

        if ($this->schema == true) {
            $schema = $this->model->getSchema();
            $serialize['schema'] = $schema->toArray();
            if ($schema->hasFilters()) {
                $serialize['filters'] = $schema->getFilters();
            }
        }

        if (array_key_exists('_serialize', $controller->viewVars)) {
            $_serialize = $controller->viewVars['_serialize'];
            foreach ($_serialize as $key) {
                $serialize->offsetSet($key, $controller->viewVars[$key]);
            }
        }
        $serialize['_serialize'] = array_keys($serialize->getArrayCopy());
        $controller->set($serialize->getArrayCopy());
    }

    /******************************************************************************************************************
    **
    ** Auth
    **
    ******************************************************************************************************************/

    public function isAuthorized($user = null)
    {
        $this->controller->setAuthorizedUser($user);
        $model = $this->model;
        $scope = $this->controller->controllerAction ? $this->controller->controllerAction : $this->request->header('controlleraction');
        $action = $this->request->params['action'];

        if ($action == 'translate') {
            return true;
        }

        if ($action == 'image') {
            return true;
        }

        $request = $this->request;
        $extra = new ArrayObject(['request' => $request]);

        // check if the scope has access to the action and the model
        $apiSecuritiesScopes = TableRegistry::get('ApiSecuritiesScopes');
        $apiSecurities = TableRegistry::get('ApiSecurities');
        $registryAlias = $model->registryAlias();

        // check if the scope is a stdObject, and converts to array
        // check if the scope is not a array, and converts to array
        if (is_object($scope)) {
            $scope = (array) $scope;
        } elseif (!is_array($scope)) {
            $scope = [$scope];
        }

        $scopeDenyValue = 0;
        $apiSecurityEntity = $apiSecurities
            ->find()
            ->where([$apiSecurities->aliasField('model') => $registryAlias])
            ->first();

        // default action for the table is not deny
        // checking of null as other restful call is not using security entity other than API
        if (!is_null($apiSecurityEntity) && $apiSecurityEntity->{$action} != $scopeDenyValue) {
            $denyActionCount = $apiSecuritiesScopes
                ->find()
                ->matching('ApiSecurities', function ($q) use ($registryAlias) {
                    return $q->where([
                        'ApiSecurities.model' => $registryAlias
                    ]);
                })
                ->matching('ApiScopes', function ($q) use ($scope) {
                    return $q->where([
                        'ApiScopes.name IN ' => $scope
                    ]);
                })
                ->where([
                    $apiSecuritiesScopes->aliasField($action) => $scopeDenyValue
                ])
                ->count();

            // if the scope has no deny value, the restful call can return as authorized
            if ($denyActionCount == 0) {
                return true;
            }
        }

        foreach ($scope as $value) {
            $event = $model->dispatchEvent('Restful.Model.isAuthorized', [$value, $action, $extra], $this);
            if ($event->result) {
                return $event->result;
            }
        }
        
        return false;
    }

    /******************************************************************************************************************
    **
    ** Controller Actions
    **
    ******************************************************************************************************************/

    public function token()
    {
        $this->controller->autoRender = false;
        if (!empty($this->request->query)) {
            pr($this->request->query);
        }
    }

    public function nothing()
    {
        $data = [];
        $this->controller->set([
            'data' => $data,
            '_serialize' => ['data']
        ]);
    }

    // this function will be called if accessed from other domain
    // Reference: http://www.html5rocks.com/en/tutorials/cors/
    // The logic in this function is not finalised
    public function options()
    {
        $supportedMethods = ['GET', 'POST', 'PATCH', 'DELETE'];
        $allowedHeaders = ['Origin', 'X-Requested-With', 'Content-Type', 'Accept', 'Authorization', 'ControllerAction'];
        $header = $this->response->getHeaderLine();
        $origin = isset($header['Origin']) ? $header['Origin'] : [];

        $this->response->cors($this->request, $origin, $supportedMethods, $allowedHeaders);

        // Default it should be UTF-8 and text/html and the following need not be set
        $this->response->charset('UTF-8');
        $this->response->type('html');

        Log::write('debug', $this->response->getHeaderLine());

        /*
        OPTIONS /cors HTTP/1.1
        Origin: http://api.bob.com
        Access-Control-Request-Method: PUT
        Access-Control-Request-Headers: X-Custom-Header
        Host: api.alice.com
        Accept-Language: en-US
        Connection: keep-alive
        User-Agent: Mozilla/5.0...
        */

        /*
        Access-Control-Allow-Origin: http://api.bob.com
        Access-Control-Allow-Methods: GET, POST, PUT
        Access-Control-Allow-Headers: X-Custom-Header
        Content-Type: text/html; charset=utf-8
        */
    }

    public function schema()
    {
        $action = $this->extra['action'];
        $this->schema = true;
        $eventMap = [
            'add' => 'Restful.Model.add.updateSchema',
            'index' => 'Restful.Model.index.updateSchema',
            'view' => 'Restful.Model.view.updateSchema',
            'edit' => 'Restful.Model.edit.updateSchema'
        ];

        if (array_key_exists($action, $eventMap)) {
            $event = $eventMap[$action];
        } else {
            $event = 'Restful.Model.' . $action . '.updateSchema';
        }

        $table = $this->initTable($this->model);
        $table->dispatchEvent($event, [$this->model->getSchema(), $this->extra], $this->controller);
    }

    //curl -i -X GET http://localhost/school/api/restful/v2/Users.json
    //curl -i -X GET http://localhost/school/api/restful/v2/Users.json?_contain=Genders
    //curl -i -X GET http://localhost/school/api/restful/v2/Users.json?_fields=username,Genders.name&_contain=Genders
    //curl -i -X GET http://localhost/school/api/restful/v2/Users.json?_order=-first_name,last_name
    //curl -i -X GET http://localhost/school/api/restful/v2/Users.json?_limit=10&_page=2

    public function index()
    {
        if (is_null($this->model)) {
            return;
        }
        $controller = $this->controller;
        $extra = $this->extra;
        $user = $controller->getAuthorizedUser();
        $extra['user'] = $user;
        $serialize = $this->serialize;
        $table = $this->initTable($this->model);
        if ($table instanceof RestfulAppTable) {
            $table->dispatchEvent('Restful.Model.index.updateSchema', [$table->getSchema(), $extra], $controller);
        }

        $query = $table->find('all', $extra->getArrayCopy());
        $this->processRequestQueries($query, $extra);

        if (!$extra->offsetExists('page')) {
            $extra->offsetSet('page', 1);
        }

        try {
            $total = $query->count();
            if ($extra->offsetExists('limit') && $extra->offsetExists('page')) {
                $query->limit($extra['limit'])->page($extra['page']);
            }
            $data = $query->toArray();
            $data = $this->formatResultSet($table, $data, $extra);
            if ($extra->offsetExists('flatten') && $extra->offsetGet('flatten') === true) {
                foreach ($data as $key => $content) {
                    $data[$key] = Hash::flatten($content->toArray());
                }
            }

            $serialize->offsetSet('data', $data);
            $serialize->offsetSet('total', $total);
        } catch (Exception $e) {
            $this->_outputError($e->getMessage());
        }
    }

    //curl -H "Content-Type: application/json" -i -X POST -d '{"openemis_no": "123456", "first_name": "restful", "last_name": "user", "gender_id": 1, "date_of_birth": "1970-01-01"}' http://localhost/school/api/restful/v2/Users.json

    public function add()
    {
        if (is_null($this->model)) {
            return;
        }
        $requestData = $this->request->data;
        $controller = $this->controller;
        $extra = $this->extra;
        $serialize = $this->serialize;
        $table = $this->initTable($this->model);
        if ($table instanceof RestfulAppTable) {
            $table->dispatchEvent('Restful.Model.add.updateSchema', [$table->getSchema(), $extra], $controller);
        }

        // // if true, from angularJS, else external API call
        $requestData['action_type'] = isset($requestData['action_type']) ? $requestData['action_type'] : 'third_party';        
        $options = ['extra' => $extra];

        $entity = $table->newEntity($requestData, $options);
        if($table->table() == 'staff_payslips'){
            // blob data type will be sent using based64 format without stream get content
            $entity = $this->convertBase64ToBinaryWithoutStreamGetContent($entity);
            $table->fileUpload($entity, $options);
        }else{
            // blob data type will be sent using based64 format
            $entity = $this->convertBase64ToBinary($entity);
            $table->save($entity, $options);
        }
        $errors = $entity->errors();
        $this->translateArray($errors);

        $data = $this->formatResultSet($table, $entity, $extra);

        // Jeff: will there be a case of flattening the array in Add?
        // if ($extra->offsetExists('flatten') && $extra->offsetGet('flatten') === true) {
        //     $data = Hash::flatten($data->toArray());
        // }

        $serialize->offsetSet('data', $data);
        $serialize->offsetSet('error', $errors);
    }

    //curl -i -X GET http://localhost/school/api/restful/v2/Users/1.json

    public function view($id)
    {
        if (is_null($this->model)) {
            return;
        }
        $controller = $this->controller;
        $extra = $this->extra;
        $serialize = $this->serialize;

        $table = $this->initTable($this->model);
        $schemaTable = $table;

        if ($extra->offsetExists('model')) {
            $table = $this->initTable($extra->offsetGet('model')); // change main model dynamically
        }

        $idKeys = $id;
        if (json_decode($this->urlsafeB64Decode($id), true)) {
            $idKeys = json_decode($this->urlsafeB64Decode($id), true);
        }
        $primaryKeyValues = $this->getIdKeys($table, $idKeys, false);

        if ($table->exists([$primaryKeyValues])) {
            // process the queries sent through the request url
            $this->processRequestQueries(null, $extra, 'view');

            if ($extra->offsetExists('finder')) {
                $finder = $extra->offsetGet('finder');
                if (!$table->hasFinder($finder)) {
                    $extra->offsetUnset('finder');
                }
            }
            $entity = $table->get($primaryKeyValues, $extra->getArrayCopy());

            $data = $this->formatResultSet($table, $entity, $extra);
            if ($extra->offsetExists('flatten') && $extra->offsetGet('flatten') === true) {
                $data = Hash::flatten($data->toArray());
            }
            $serialize->offsetSet('data', $data);

            if ($schemaTable instanceof RestfulAppTable) {
                $action = $extra['action'];
                $eventKey = 'Restful.Model.view.updateSchema';
                if ($action != 'view') {
                    $eventKey = 'Restful.Model.' . $action . '.updateSchema';
                }
                $schemaTable->dispatchEvent($eventKey, [$schemaTable->getSchema(), $entity, $extra], $controller);
            }
        } else {
            $this->_outputError('Record does not exists');
        }
    }

    //curl -H "Content-Type: application/json" -i -X PATCH -d '{"id": 1, "username": "new_username"}' http://localhost/school/api/restful/v2/Users.json

    public function edit()
    {
        if (is_null($this->model)) {
            return;
        }

        $requestData = $this->request->data;
        $controller = $this->controller;
        $extra = $this->extra;
        $serialize = $this->serialize;
        $table = $this->initTable($this->model);

        $primaryKeyValues = $this->getIdKeys($table, $requestData, false);
        if ($table->exists([$primaryKeyValues])) {
            $entity = $table->get($primaryKeyValues, $extra->getArrayCopy());
            $options = ['extra' => $extra];
            $entity = $table->patchEntity($entity, $requestData, $options);

            $entity = $this->convertBase64ToBinary($entity);
            $table->save($entity, $options);
            $errors = $entity->errors();
            $this->translateArray($errors);
            $data = $this->formatResultSet($table, $entity, $extra);
            if ($extra->offsetExists('flatten') && $extra->offsetGet('flatten') === true) {
                $data = Hash::flatten($data->toArray());
            }

            $serialize->offsetSet('data', $data);
            $serialize->offsetSet('error', $errors);
        } else {
            $this->_outputError('Record does not exists');
        }
    }

    //curl -H "Content-Type: application/json" -i -X DELETE -d '{"id": 1}' http://localhost/school/api/restful/v2/Users.json

    public function delete()
    {
        if (is_null($this->model)) {
            return;
        }

        $requestData = $this->request->data;
        $extra = $this->extra;
        $serialize = $this->serialize;
        $table = $this->initTable($this->model);

        $primaryKeyValues = $this->getIdKeys($table, $requestData, false);
        if ($table->exists([$primaryKeyValues])) {
            $entity = $table->get($primaryKeyValues);
            $message = __('Successful');
            if (!$table->delete($entity, $extra->getArrayCopy())) {
                $message = __('Not Successful');
            }

            $serialize->offsetSet('result', $message);
            $serialize->offsetSet('error', $entity->errors());
        } else {
            $this->_outputError('Record does not exists');
        }
    }

    /******************************************************************************************************************
    **
    ** Http URL instructions for database queries
    **
    ******************************************************************************************************************/

    private function _search($query, $value, ArrayObject $extra)
    {
        $value = $this->urlsafeB64Decode($value);
        $types = ['string', 'text'];
        $table = $this->model;
        $schema = $table->getSchema();
        $columns = $table->schema()->columns();

        $OR = [];
        foreach ($schema as $field) {
            $name = $field->name();
            if ($name == 'id' || $field->controlType() == 'password') {
                continue;
            }

            // if the field is of a searchable type and it is part of the table schema
            if (in_array($field->type(), $types) && in_array($name, $columns)) {
                $wildcard = $field->wildcard();
                $searchValue = '%' . $value . '%';
                if ($wildcard == 'left') {
                    $searchValue = '%' . $value;
                } elseif ($wildcard == 'right') {
                    $searchValue = $value . '%';
                }
                $OR[$table->aliasField($name) . ' LIKE'] = $searchValue;
            }
        }

        if ($table->hasFinder('search')) {
            $query->find('search', ['OR' => $OR]);
        } else {
            // may have problems with complicated conditions
            if (!empty($OR)) {
                $query->where(['OR' => $OR]);
            }
        }
    }

    private function _fields($query, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            if (!array_key_exists('fields', $extra)) {
                $extra['fields'] = [];
            }
            $table = $this->model;
            $columns = $table->schema()->columns();

            $fields = explode(',', $value);
            $extra['schema_fields'] = $fields;
            foreach ($fields as $index => $field) {
                if (strpos($field, ':')) { // to assign an alias to the field
                    list($alias, $value) = explode(':', $field);
                    $fields[$alias] = $value;
                    unset($fields[$index]);
                } elseif (in_array($field, $columns)) {
                    $fields[$index] = $table->aliasField($field);
                }
            }
            if (!is_null($query)) {
                $query->select($fields);
            }
            $extra['fields'] = array_merge($extra['fields'], $fields);
        }
    }

    private function _finder($query, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $table = $this->model;
            $finders = $this->decode($value);

            foreach ($finders as $name => $options) {
                $finderFunction = 'find' . ucfirst($name);
                if ($table->hasFinder($name)) {
                    if (!is_null($query)) { // for index
                        $options['_controller'] = $this->controller;
                        $query->find($name, $options);
                    } elseif (!array_key_exists('finder', $extra)) { // for view／edit
                        $extra['_controller'] = $this->controller;
                        $extra['finder'] = $name;
                    }
                } else {
                    Log::write('debug', 'Finder (' . $finderFunction . ') does not exists.');
                }
            }
        }
    }

    private function _innerJoinWith($query, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $innerJoinAssoc = [];

            if (strpos($value, ',')) {
                $innerJoinAssoc[] = $value;
            } else {
                $innerJoinAssoc = explode(',', $value);
            }

            if (!is_null($query)) {
                foreach ($innerJoinAssoc as $assoc) {
                    $query->innerJoinWith($assoc);
                }
            }
            $extra['innerJoinWith'] = $innerJoinAssoc;
        }
    }

    private function _leftJoinWith($query, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $leftJoinAssoc = [];

            if (strpos($value, ',')) {
                $leftJoinAssoc[] = $value;
            } else {
                $leftJoinAssoc = explode(',', $value);
            }

            if (!is_null($query)) {
                foreach ($leftJoinAssoc as $assoc) {
                    $query->leftJoinWith($assoc);
                }
            }
            $extra['leftJoinWith'] = $leftJoinAssoc;
        }
    }

    private function _contain($query, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $contain = [];
            $table = $this->model;
            $isDebug = Configure::read('debug');

            $valueArr = explode(',', $value);
            foreach ($valueArr as $item) {
                if ($item === 'true' && $isDebug) { // contains all BelongsTo associations
                    $this->serialize->offsetSet('warning', 'contain=true is only available in development mode');
                    foreach ($table->associations() as $assoc) {
                        if ($assoc->type() == 'manyToOne') {
                            $contain[] = $assoc->name();
                        }
                    }
                    break;
                } else {
                    $contain[] = $item;
                }
            }

            if (!empty($contain)) {
                if (!is_null($query)) {
                    $query->contain($contain);
                } else {
                    $extra['contain'] = $contain;
                }
            }
        }
    }

    private function _conditions($query, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $conditions = [];
            $table = $query->repository();
            $columns = $table->schema()->columns();

            foreach ($value as $field => $val) {
                $compareLike = false;
                if ($this->startsWith($val, '_')) {
                    $val = '%' . substr($val, 1);
                    $compareLike = true;
                }

                if ($this->endsWith($val, '_')) {
                    $val = substr($val, 0, strlen($val)-1) . '%';
                    $compareLike = true;
                }

                if ($compareLike) {
                    $field .= ' LIKE';
                }

                if (in_array($field, $columns)) {
                    $conditions[$table->aliasField($field)] = $val;
                } else {
                    $conditions[str_replace("-", ".", $field)] = $val;
                }
            }
            if (!is_null($query)) {
                $query->where($conditions);
            }
            $extra['conditions'] = $conditions;
        }
    }

    private function _orWhere($query, $value, ArrayObject $extra)
    {
        $table = $extra['table'];
        $fields = explode(',', $value);
        $columns = $table->schema()->columns();

        $orWhere = [];
        foreach ($fields as $field) {
            $values = explode(':', $field);
            $key = $values[0];
            $value = $values[1];

            if (in_array($key, $columns)) {
                $key = $table->aliasField($key);
            }

            $compareLike = false;
            if ($this->startsWith($value, '_')) {
                $value = '%' . substr($value, 1);
                $compareLike = true;
            }

            if ($this->endsWith($value, '_')) {
                $value = substr($value, 0, strlen($value)-1) . '%';
                $compareLike = true;
            }

            if ($compareLike) {
                $key .= ' LIKE';
            }
            $orWhere[$key] = $value;
            if (!is_null($query)) {
                $query->orWhere([$key => $value]);
            }
        }
        if (!empty($orWhere)) {
            $extra['orWhere'] = $orWhere;
        }
    }

    private function _group($query, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $fields = explode(',', $value);
            if (!is_null($query)) {
                $query->group($fields);
            }
        }
    }

    private function _order($query, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $fields = explode(',', $value);
            if (!is_null($query)) {
                $order = [];
                foreach ($fields as $field) {
                    if ($this->startsWith($field, '-')) {
                        $order[substr($field, 1, strlen($field))] = 'desc';
                    } else {
                        $order[$field] = 'asc';
                    }
                }
                $query->order($order);
            }
        }
    }

    private function _limit($query, $value, ArrayObject $extra)
    {
        /*
        1. query does not contain _limit
        2. query contains _limit=0
        3. query contains _limit=-1
        3. query contains _limit=10
        */
        if ($value > 0) {
            $extra['limit'] = $value;
        } elseif ($value < 0) {
            $extra['limit'] = 30;
        }
    }

    private function _page($query, $value, ArrayObject $extra)
    {
        if (empty($value)) {
            $value = 1;
        }
        $extra['page'] = $value;
    }

    private function _showBlobContent(Query $query = null, $value, ArrayObject $extra)
    {
        $extra['blobContent'] = $value;
    }

    /******************************************************************************************************************
    **
    ** Helper functions
    **
    ******************************************************************************************************************/

    private function initRequestQueries(Table $table)
    {
        $requestQueries = $this->request->query;
        if (array_key_exists('_querystring', $requestQueries)) {
            $queryString = $this->urlsafeB64Decode($requestQueries['_querystring']);
            unset($this->request->query['_querystring']);
            $this->extra['querystring'] = json_decode($queryString, true);
        }

        if (array_key_exists('_search', $requestQueries)) {
            $search = $this->urlsafeB64Decode($requestQueries['_search']);
            $this->extra['search'] = $search;
        }

        if (!array_key_exists('_limit', $requestQueries)) {
            $this->extra['limit'] = 30;
        }

        if (array_key_exists('_schema', $requestQueries) && $requestQueries['_schema'] == 'true') {
            unset($this->request->query['_schema']);
            $this->schema = true;
        }
        // onBuildSchema will always be called to build the schema, but $this->schema will control if the schema information
        // should be included in the json response
        $event = $table->dispatchEvent('Restful.Model.onBuildSchema', [$this->extra], $this->controller);
        if (array_key_exists('_flatten', $requestQueries) && $requestQueries['_flatten'] == true) {
            unset($this->request->query['_flatten']);
            $this->extra['flatten'] = true;
        }

        if (array_key_exists('_action', $requestQueries)) {
            unset($this->request->query['_action']);
            $this->extra['action'] = $requestQueries['_action'];
        } else {
            $this->extra['action'] = $this->request->action;
        }

        $event = $table->dispatchEvent('Restful.Model.onBeforeAction', [$this->extra], $this->controller);
    }

    private function processRequestQueries($query, ArrayObject $extra, $action = 'index')
    {
        $requestQueries = $this->request->query;

        $conditions = [];
        foreach ($requestQueries as $key => $value) {
            if (!$this->startsWith($key, '_')) {
                $conditions[$key] = $value;
                unset($requestQueries[$key]);
            }
        }
        if (!empty($conditions)) {
            $requestQueries['_conditions'] = $conditions;
            $extra['conditions'] = $conditions;
        }

        // methods have to be executed in the correct sequence
        $indexMethods = ['_search', '_fields', '_finder', '_contain', '_innerJoinWith', '_leftJoinWith', '_conditions', '_orWhere', '_group', '_order', '_limit', '_page'];
        $viewMethods = ['_fields', '_finder', '_contain'];

        if ($action == 'index') {
            $methods = $indexMethods;
        } else {
            $methods = $viewMethods;
        }

        foreach ($methods as $method) {
            if (array_key_exists($method, $requestQueries)) {
                $this->$method($query, $requestQueries[$method], $extra);
            }
        }
    }

    // to convert string into json string, and decode into php array
    private function decode($value)
    {
        $list = [];
        $queryArray = explode(',', $value);

        foreach ($queryArray as $json) {
            // to convert to a proper json string for decoding into php array
            $json = str_replace(':', '":"', $json);
            $json = str_replace('[', '":{"', $json);
            $json = str_replace(']', '"}}', $json);
            $json = '{"' . str_replace(';', '","', $json);

            $noAttributesFound = strripos($json, '"}') === false;
            if ($noAttributesFound) {
                $json .= '": {}}';
            }
            $array = json_decode($json, true);
            $list = array_merge($list, $array);
        }
        return $list;
    }

    private function formatData(Entity $entity)
    {
        $table = $this->model;
        $schema = $table->schema();
        foreach ($entity->visibleProperties() as $property) {
            $method = $schema->columnType($property);
            if (method_exists($this, $method)) {
                $entity->$property = $this->$method($entity->property);
            }
        }
    }

    private function binary($attribute)
    {
        return base64_encode($attribute);
    }

    private function convertBinaryToBase64(Table $table, Entity $entity, ArrayObject $extra)
    {
        foreach ($entity->visibleProperties() as $property) {
            if ($entity->$property instanceof Entity) {
                $source = $entity->$property->source();
                $_connectionName = $this->request->query('_db') ? $this->request->query('_db') : 'default';
                if (!TableRegistry::exists($source)) {
                    $entityTable = TableRegistry::get($source, ['connectionName' => $_connectionName]);
                } else {
                    $entityTable = TableRegistry::get($source);
                }

                $this->convertBinaryToBase64($entityTable, $entity->$property, $extra);
            } elseif (is_array($entity->$property)) {
                foreach ($entity->$property as $propertyEntity) {
                    if ($propertyEntity instanceof Entity) {
                        $source = $propertyEntity->source();
                        $_connectionName = $this->request->query('_db') ? $this->request->query('_db') : 'default';
                        if (!TableRegistry::exists($source)) {
                            $entityTable = TableRegistry::get($source, ['connectionName' => $_connectionName]);
                        } else {
                            $entityTable = TableRegistry::get($source);
                        }
                        $this->convertBinaryToBase64($entityTable, $propertyEntity, $extra);
                    }
                }
            } else {
                if ($property == 'password') {
                    $entity->unsetProperty($property);
                }
                $columnType = $table->schema()->columnType($property);
                $method = 'format'. ucfirst($columnType);
                $eventKey = 'Restful.Model.onRender'.ucfirst($columnType);
                $event = $table->dispatchEvent($eventKey, [$entity, $property, $extra], $this);
                if ($event->result) {
                    $entity->$property = $event->result;
                } elseif (method_exists($this, $method)) {
                    $entity->$property = $this->$method($entity->$property, $extra);
                }
            }
        }
    }

    private function formatBinary($attribute, $extra)
    {
        if ($extra->offsetExists('blobContent') && $extra['blobContent'] == true) {
            if (is_resource($attribute)) {
                return base64_encode(stream_get_contents($attribute));
            } else {
                return base64_encode($attribute);
            }
        }
    }

    private function formatDatetime($attribute, $extra)
    {
        return $this->formatDate($attribute, $extra);
    }

    private function formatDate($attribute, $extra)
    {
        if ($attribute instanceof MutableDate || $attribute instanceof Date) {
            $attribute = $attribute->format('Y-m-d');
        } elseif ($attribute == '0000-00-00') {
            $attribute = '1970-01-01';
        }
        return $attribute;
    }

    private function formatTime($attribute, $extra)
    {
        if ($attribute instanceof MutableDateTime || $attribute instanceof Chronos) {
            $attribute = $attribute->format('H:i:s');
        }
        return $attribute;
    }

    private function convertBase64ToBinary(Entity $entity)
    {
        $table = $this->model;
        $schema = $table->schema();
        $columns = $schema->columns();

        foreach ($columns as $column) {
            $attr = $schema->column($column);
            if ($attr['type'] == 'binary' && $entity->has($column)) {
                if (is_resource($entity->$column)) {
                    $entity->$column = stream_get_contents($entity->$column);
                } else {
                    $value = urldecode($entity->$column);
                    $entity->$column = base64_decode($value);
                }
            }
        }
        return $entity;
    }

    private function convertBase64ToBinaryWithoutStreamGetContent(Entity $entity)
    {
        $table = $this->model;
        $schema = $table->schema();
        $columns = $schema->columns();

        foreach ($columns as $column) {
            $attr = $schema->column($column);
            if ($attr['type'] == 'binary' && $entity->has($column)) {
                $entity->$column = base64_decode($entity->$column);
            }
        }
        return $entity;
    }

    private function formatResultSet(Table $table, $data, $extra)
    {
        if ($data instanceof Entity) {
            $this->convertBinaryToBase64($table, $data, $extra);
        } elseif (is_array($data)) {
            foreach ($data as $value) {
                if ($value instanceof Entity) {
                    $this->convertBinaryToBase64($table, $value, $extra);
                }
            }
        }
        return $data;
    }

    /**
     * Decode a string with URL-safe Base64.
     *
     * @param string $input A Base64 encoded string
     *
     * @return string A decoded string
     */
    public function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Encode a string with URL-safe Base64.
     *
     * @param string $input The string you want encoded
     *
     * @return string The base64 encode of what you passed in
     */
    public function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    private function initTable(Table $table, $connectionName = 'default')
    {
        $_connectionName = $this->request->query('_db') ? $this->request->query('_db') : $connectionName;
        if (method_exists($table, 'setConnectionName')) {
            $table::setConnectionName($_connectionName);
        }
        return $table;
    }

    private function instantiateModel($model)
    {
        $model = str_replace('-', '.', $model);
        if (Configure::read('debug')) {
            $_connectionName = $this->request->query('_db') ? $this->request->query('_db') : 'default';
            $target = TableRegistry::get($model, ['connectionName' => $_connectionName]);
        } else {
            $target = TableRegistry::get($model);
        }

        try {
            $target->find('all')->limit('1');
            return $target;
        } catch (Exception $e) {
            $this->_outputError();
            return false;
        }
    }

    private function _outputError($message = 'Requested Plugin-Model does not exists')
    {
        $model = str_replace('-', '.', $this->request->params['model']);
        $this->controller->set([
            'model' => $model,
            'error' => $message,
            'request_method' => $this->request->method(),
            'action' => $this->request->params['action'],
            '_serialize' => ['request_method', 'action', 'model', 'error']
        ]);
    }

    private function startsWith($haystack, $needle)
    {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    private function endsWith($haystack, $needle)
    {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

    private function getIdKeys(Table $model, $ids, $addAlias = true)
    {
        $primaryKey = $model->primaryKey();
        $idKeys = [];
        if (!empty($ids)) {
            if (is_array($primaryKey)) {
                $count = count($primaryKey);
                $keysPresent = array_intersect($primaryKey, array_keys($ids));
                if (count($keysPresent) != $count) {
                    throw new InvalidPrimaryKeyException('The following primary keys ['.implode(', ', array_diff($primaryKey, array_keys($ids))). '] are not found in the request.');
                }
                foreach ($primaryKey as $key) {
                    if ($addAlias) {
                        $idKeys[$model->aliasField($key)] = $ids[$key];
                    } else {
                        $idKeys[$key] = $ids[$key];
                    }
                }
            } else {
                if (is_array($ids)) {
                    $ids = $ids[$primaryKey];
                }
                if ($addAlias) {
                    $idKeys[$model->aliasField($primaryKey)] = $ids;
                } else {
                    $idKeys[$primaryKey] = $ids;
                }
            }
        }
        return $idKeys;
    }

    public function translateArray(&$array)
    {
        $translateItem = function (&$item, $key) {
            $item = __($item);
        };
        array_walk_recursive($array, $translateItem);
    }
}
