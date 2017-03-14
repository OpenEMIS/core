<?php
namespace Restful\Controller\Component;

use Exception;
use ArrayObject;
use Cake\Log\Log;
use Cake\Utility\Hash;
use Cake\ORM\Entity;
use Restful\Controller\RestfulInterface;
use Cake\Controller\Component;
use Cake\ORM\Table;
use Restful\Traits\RestfulV2Trait as RestfulTrait;

class RestfulV2Component extends Component implements RestfulInterface
{
    use RestfulTrait;
    private $model = null;
    private $controller = null;
    private $Auth = null;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->controller = $this->_registry->getController();
        $this->Auth = $this->controller->Auth;
        $this->model = $this->config('model');
    }

    public function token()
    {
        $this->controller->autoRender = false;
        if (!empty($this->request->query)) {
            pr($this->request->query);
        }
    }

    public function nothing()
    {
        $this->_outputData([]);
    }

    // this function will be called if accessed from other domain
    // Reference: http://www.html5rocks.com/en/tutorials/cors/
    // The logic in this function is not finalised
    public function options()
    {
        $supportedMethods = ['GET', 'POST', 'PATCH', 'DELETE'];
        $allowedHeaders = ['Origin', 'X-Requested-With', 'Content-Type', 'Accept', 'Authorization', 'ControllerAction'];
        $header = $this->response->header();
        $origin = isset($header['Origin']) ? $header['Origin'] : [];

        $this->response->cors($this->request, $origin, $supportedMethods, $allowedHeaders);

        // Default it should be UTF-8 and text/html and the following need not be set
        $this->response->charset('UTF-8');
        $this->response->type('html');

        Log::write('debug', $this->response->header());

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


    public function index()
    {
        if (is_null($this->model)) {
            return;
        }
        $table = $this->initTable($this->model);
        $query = $table->find();
        $requestQueries = $this->request->query;
        $user = $this->controller->getUser();
        $extra = new ArrayObject(['table' => $table, 'fields' => [], 'schema_fields' => [], 'action' => 'custom', 'functionName' => 'index', 'user' => $user]);

        $this->processQueryString($requestQueries, $query, $extra);

        try {
            $data = [];
            $serialize = [];
            $schema = $this->processSchema($table, $extra);
            $action = $extra['action'];
            if ($extra->offsetExists('list') && $extra['list'] == true) {
                $data = $query->toArray();
                $serialize = ['data' => $data];
            } else {
                $total = $query->count();
                if ($extra->offsetExists('limit') && $extra->offsetExists('page')) {
                    $query->limit($extra['limit'])->page($extra['page']);
                }
                $extra['query'] = $query;
                $data = $query->toArray();
                $event = $table->dispatchEvent('Restful.Model.onAfterQuery', [$data, $extra], $this->controller);
                if ($event->result) {
                    $data = $event->result;
                } else {
                    $data = $this->formatResultSet($table, $data, $extra);
                    if ($extra->offsetExists('flatten')) {
                        $data = $data->toArray();
                        foreach ($data as $key => $content) {
                            $data[$key] = Hash::flatten($content->toArray());
                        }
                    }
                }
                $event = $table->dispatchEvent('Restful.Model.onAfterFormatResult', [$data, $schema, $extra], $this->controller);
                if ($event->result) {
                    $data = $event->result;
                }
                if ($extra->offsetExists('showSchema') && $extra['showSchema']) {
                    $serialize = ['data' => $data, 'schema' => $schema->getArrayCopy(), 'total' => $total];
                } else {
                    $serialize = ['data' => $data, 'total' => $total];
                }
            }
            $serialize['_serialize'] = array_keys($serialize);
            $this->controller->set($serialize);
        } catch (Exception $e) {
            $this->_outputError($e->getMessage());
        }
    }

    public function add()
    {
        $table = $this->initTable($this->model);
        Log::write('debug', 'in add');
        if ($table) {
            Log::write('debug', 'in add -> table found');
            $user = $this->controller->getUser();
            $extra = new ArrayObject(['table' => $table, 'action' => 'custom', 'functionName' => 'add', 'blobContent' => true, 'user' => $user]);
            $requestQueries = $this->request->query;
            $this->processQueryString($requestQueries, null, $extra);
            $action = $extra['action'];
            $options = ['extra' => $extra];
            Log::write('debug', 'in add -> before new entity');
            $entity = $table->newEntity($this->request->data, $options);
            Log::write('debug', 'in add -> after patching');
            $entity = $this->convertBase64ToBinary($entity);
            $table->save($entity, $options);
            Log::write('debug', 'in add -> after save');
            $errors = $entity->errors();
            $data = $this->formatResultSet($table, $entity, $extra);
            if ($extra->offsetExists('flatten') && $extra['flatten'] === true) {
                $data = Hash::flatten($data->toArray());
            }
            $this->controller->set([
                'data' => $data,
                'error' => $errors,
                '_serialize' => ['data', 'error']
            ]);
        }
    }

    public function view($id)
    {
        if (is_null($this->model)) {
            return;
        }
        $table = $this->initTable($this->model);
        $user = $this->controller->getUser();
        $extra = new ArrayObject(['table' => $table, 'fields' => [], 'schema_fields' => [], 'action' => 'custom', 'functionName' => 'view', 'blobContent' => true, 'user' => $user]);
        $primaryKey = [];
        if (strtolower($id) != 'schema') {
            $idKeys = $id;
            if (json_decode($this->urlsafeB64Decode($id), true)) {
                $idKeys = json_decode($this->urlsafeB64Decode($id), true);
            }
            $primaryKey = $this->getIdKeys($table, $idKeys, false);
            $extra['primaryKey'] = $primaryKey;
        }

        $requestQueries = $this->request->query;
        $this->processQueryString($requestQueries, null, $extra);
        $schema = $this->processSchema($table, $extra);
        $action = $extra['action'];

        if (strtolower($id) == 'schema') {
            $serialize = ['schema' => $schema];
            $serialize['_serialize'] = array_keys($serialize);
            $this->controller->set($serialize);
        } else {
            if ($table->exists([$extra['primaryKey']])) {
                $queryString = $this->request->query;
                $flatten = false;
                $query = null;
                if (empty($extra['fields'])) {
                    unset($extra['fields']);
                }
                if (isset($extra['flatten']) && $extra['flatten'] === true) {
                    $flatten = true;
                }
                $event = $table->dispatchEvent('Restful.Model.onBeforeGetData', [$action, $extra], $this->controller);
                if ($event->result) {
                    $data = $event->result;
                } else {
                    $data = $table->get($extra['primaryKey'], $extra->getArrayCopy());
                }
                $event = $table->dispatchEvent('Restful.Model.onAfterQuery', [$data, $extra], $this->controller);
                if ($event->result) {
                    $data = $event->result;
                } else {
                    $data = $this->formatResultSet($table, $data, $extra);
                    if ($flatten) {
                        $data = Hash::flatten($data->toArray());
                    }
                }
                $event = $table->dispatchEvent('Restful.Model.onAfterFormatResult', [$data, $schema, $extra], $this->controller);
                if ($event->result) {
                    $data = $event->result;
                }
                if ($extra->offsetExists('showSchema') && $extra['showSchema']) {
                    $serialize = ['data' => $data, 'schema' => $schema->getArrayCopy()];
                } else {
                    $serialize = ['data' => $data];
                }
                $serialize['_serialize'] = array_keys($serialize);
                $this->controller->set($serialize);
            } else {
               $this->_outputError('Record does not exists');
            }
        }
    }

    public function edit()
    {
        $target = $this->initTable($this->model);
        if ($target) {
            $requestData = $this->request->data;
            $user = $this->controller->getUser();
            $extra = new ArrayObject(['table' => $target, 'action' => 'custom', 'functionName' => 'edit', 'blobContent' => true, 'user' => $user]);
            $requestQueries = $this->request->query;
            $this->processQueryString($requestQueries, null, $extra);
            $action = $extra['action'];
            $primaryKeyValues = $this->getIdKeys($target, $requestData, false);
            if ($target->exists([$primaryKeyValues])) {
                $entity = $target->get($primaryKeyValues);
                $options = ['extra' => $extra];
                $entity = $target->patchEntity($entity, $requestData, $options);

                $entity = $this->convertBase64ToBinary($entity);
                $target->save($entity, $options);
                $errors = $entity->errors();
                $data = $this->formatResultSet($target, $entity, $extra);
                if (isset($extra['flatten']) && $extra['flatten'] === true) {
                    $data = Hash::flatten($data->toArray());
                }
                $this->controller->set([
                    'data' => $data,
                    'error' => $errors,
                    '_serialize' => ['data', 'error']
                ]);
            } else {
                $this->_outputError('Record does not exists');
            }
        }
    }

    public function delete()
    {
        $target = $this->model;
        $user = $this->controller->getUser();
        $extra = new ArrayObject(['table' => $target, 'fields' => [], 'schema_fields' => [], 'action' => 'custom', 'functionName' => 'delete', 'user' => $user]);
        $requestQueries = $this->request->query;
        $this->processQueryString($requestQueries, null, $extra);
        $action = $extra['action'];
        if ($target) {
            $requestData = $this->request->data;
            if (!is_array($target->primaryKey())) {
                $primaryKey = [$target->primaryKey()];
            } else {
                // composite keys
                $primaryKey = $target->primaryKey();
            }
            $flipKey = array_flip($primaryKey);
            $keyCount = count($primaryKey);
            $keyValues = array_intersect_key($requestData, $flipKey);
            if (count($keyValues) != $keyCount) {
                // throw exception
            }
            $primaryKeyValues = $this->getIdKeys($target, $keyValues);

            if ($target->exists([$primaryKeyValues])) {
                $entity = $target->get($primaryKeyValues);
                $message = __('Successful');
                if (!$target->delete($entity, $extra->getArrayCopy())) {
                    $message = __('Not Successful');
                }
                $this->controller->set([
                    'data' => $entity,
                    'error' => $entity->errors(),
                    'result'=> $message,
                    '_serialize' => ['data', 'error', 'result']
                ]);
            } else {
                $this->_outputError('Record does not exists');
            }
        }
    }
}
