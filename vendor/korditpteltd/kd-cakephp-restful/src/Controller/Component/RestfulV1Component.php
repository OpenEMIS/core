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
use Restful\Traits\RestfulV1Trait as RestfulTrait;

class RestfulV1Component extends Component implements RestfulInterface
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

        $table = $this->model;
        $query = $table->find();
        $requestQueries = $this->request->query;
        $extra = new ArrayObject(['table' => $table, 'fields' => []]);
        Log::write('debug', $requestQueries);

        $default = ['_limit' => 30, '_page' => 1];
        $queryString = array_merge($default, $this->processQueryString($requestQueries));

        foreach ($queryString as $key => $attr) {
            $this->$key($query, $attr, $extra);
        }
        if (array_key_exists('_fields', $queryString) && !empty($extra['fields'])) {
            $query->select($extra['fields']);
        }

        try {
            $data = [];
            $serialize = [];
            if ($extra->offsetExists('list') && $extra['list'] == true) {
                $data = $query->toArray();
                $serialize = ['data' => $data];
            } else {
                $total = $query->count();
                if ($extra->offsetExists('limit') && $extra->offsetExists('page')) {
                    $query->limit($extra['limit'])->page($extra['page']);
                }
                $data = $this->formatResultSet($table, $query->all());
                if ($extra->offsetExists('flatten')) {
                    $data = $data->toArray();
                    foreach ($data as $key => $content) {
                        $data[$key] = Hash::flatten($content->toArray());
                    }
                }
                $serialize = ['data' => $data, 'total' => $total];
            }
            $serialize['_serialize'] = array_keys($serialize);
            $this->controller->set($serialize);
        } catch (Exception $e) {
            $this->_outputError($e->getMessage());
        }
    }

    public function add()
    {
        $target = $this->model;
        if ($target) {
            $entity = $target->newEntity($this->request->data);
            $entity = $this->convertBase64ToBinary($entity);
            $target->save($entity);
            $this->formatData($entity);
            $this->controller->set([
                'data' => $entity,
                'error' => $entity->errors(),
                '_serialize' => ['data', 'error']
            ]);
        }
    }

    public function view($id)
    {
        if (is_null($this->model)) {
            return;
        }

        $table = $this->model;

        if (strtolower($id) == 'schema') {
            $extra = new ArrayObject([]);
            $schema = [];
            $event = $table->dispatchEvent('Restful.Model.onSetupFields', [$extra], $this);
            if (is_array($event->result)) {
                $schema = $event->result;
            }
            $serialize = ['data' => $schema];
            $serialize['_serialize'] = array_keys($serialize);
            $this->controller->set($serialize);
        } else {
            if ($table->exists([$table->primaryKey() => $id])) {
                $primaryKey = $this->getIdKeys($table, [$table->primaryKey() => $id]);
                $this->viewEntity($table, $primaryKey);
            } else if ($this->urlsafeB64Decode($id) && $table->exists([json_decode($this->urlsafeB64Decode($id), true)])) {
                $primaryKey = $this->getIdKeys($table, json_decode($this->urlsafeB64Decode($id), true));
                $this->viewEntity($table, $primaryKey);
            } else {
                $this->_outputError('Record does not exists');
            }
        }
    }

    public function edit()
    {
        $target = $this->model;
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
                $entity = $table->get($primaryKeyValues);
                $entity = $table->patchEntity($entity, $requestData);
                $entity = $this->convertBase64ToBinary($entity);
                $table->save($entity);
                $this->controller->set([
                    'data' => $entity,
                    'error' => $entity->errors(),
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
                $message = 'Deleted';
                if (!$target->delete($entity)) {
                    $message = 'Error';
                }
                $this->controller->set([
                    'result'=> $message,
                    '_serialize' => ['result']
                ]);
            } else {
                $this->_outputError('Record does not exists');
            }
        }
    }
}
