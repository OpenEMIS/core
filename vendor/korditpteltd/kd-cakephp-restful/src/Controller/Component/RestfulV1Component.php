<?php
namespace Restful\Controller\Component;

use Exception;
use ArrayObject;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Controller\Component;
use Cake\Utility\Hash;
use Cake\Log\Log;

use Restful\Controller\RestfulInterface;
use Restful\Traits\RestfulV1Trait as RestfulTrait;

class RestfulV1Component extends Component implements RestfulInterface
{
    use RestfulTrait;
    private $model = null;
    private $controller = null;

    public $components = ['Auth'];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->controller = $this->_registry->getController();
        $this->model = $this->config('model');
    }

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
            }
        }
    }

    public function isAuthorized($user = null)
    {
        $allowedActions = ['translate'];

        $this->controller->setAuthorizedUser($user);
        $model = $this->model;
        $scope = $this->request->header('controlleraction');
        $action = $this->request->params['action'];

        if (in_array($action, $allowedActions)) {
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

    public function index()
    {
        if (is_null($this->model)) {
            return;
        }

        $table = $this->model;
        $user = $this->controller->getAuthorizedUser();
        $query = $table->find('all', ['user' => $user]);
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
        $requestData = $this->request->data;
        $requestData['action_type'] = isset($requestData['action_type']) ? $requestData['action_type'] : 'third_party';
     
        if ($target) {
            $entity = $target->newEntity($requestData);
            $entity = $this->convertBase64ToBinary($entity);
            $target->save($entity);
            $this->formatData($entity);
            $errors = $entity->errors();
            $this->translate($errors);
            $this->controller->set([
                'data' => $entity,
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
            } elseif ($this->urlsafeB64Decode($id) && $table->exists([json_decode($this->urlsafeB64Decode($id), true)])) {
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
                $entity = $target->get($primaryKeyValues);
                $entity = $target->patchEntity($entity, $requestData);
                $entity = $this->convertBase64ToBinary($entity);
                $target->save($entity);
                $errors = $entity->errors();
                $this->translate($errors);
                $this->controller->set([
                    'data' => $entity,
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

    public function translate(&$array)
    {
        $translateItem = function (&$item, $key) {
            $item = __($item);
        };
        array_walk_recursive($array, $translateItem);
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
            $data = $target->find('all')->limit('1');
            return $target;
        } catch (Exception $e) {
            $this->_outputError();
            return false;
        }
    }
}
