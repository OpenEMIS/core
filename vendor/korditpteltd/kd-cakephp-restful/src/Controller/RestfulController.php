<?php
namespace Restful\Controller;

use Exception;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;
use Restful\Controller\AppController;

class RestfulController extends AppController
{
    private $_debug = false;
    private $model = null;

    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->loadComponent('Auth');
    }

/***************************************************************************************************************************************************
 *
 * CakePHP events
 *
 ***************************************************************************************************************************************************/
    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        if (empty($this->request->params['_ext'])) {
            $this->request->params['_ext'] = 'json';
        }

        if (isset($this->request->model)) {
            $tableAlias = $this->request->model;
            $model = $this->_instantiateModel($tableAlias);
            if ($model != false) {
                $this->model = $model;

                // Event to get allowed action and allowed table to be accessible via restful
                $event = $model->dispatchEvent('Restful.Model.onGetAllowedActions', null, $this);
                if (is_array($event->result)) {
                    $this->Auth->allow($event->result);
                }
            }
        }

        if ($this->request->is(['put', 'post', 'delete', 'patch']) || !empty($this->request->data)) {
            $token = isset($this->request->cookies['csrfToken']) ? $this->request->cookies['csrfToken'] : '';
            $this->request->env('HTTP_X_CSRF_TOKEN', $token);
        }
    }

    public function beforeRender(Event $event)
    {
        parent::beforeRender($event);
        if ($this->_debug) {
            $_serialize = array_merge(['request_method', 'action'], $this->viewVars['_serialize']);
            $this->set([
                'request_method' => $this->request->method(),
                'action' => $this->request->params['action'],
                '_serialize' => $_serialize
            ]);
        }
    }


/***************************************************************************************************************************************************
 *
 * Controller action functions
 *
 ***************************************************************************************************************************************************/

    public function nothing()
    {
        $this->_outputData([]);
    }

    private function processQueryString($requestQueries)
    {
        $conditions = [];
        foreach ($requestQueries as $key => $value) {
            if (!$this->startsWith($key, '_')) {
                $conditions[$key] = $value;
                unset($requestQueries[$key]);
            }
        }
        if (!empty($conditions)) {
            $requestQueries['_conditions'] = $conditions;
        }
        return $requestQueries;
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

    private function _fields(Query $query, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $table = $extra['table'];
            $columns = $table->schema()->columns();

            $fields = explode(',', $value);
            foreach ($fields as $index => $field) {
                if (in_array($field, $columns)) {
                    $fields[$index] = $table->aliasField($field);
                }
            }
            $extra['fields'] = array_merge($extra['fields'], $fields);
        }
    }

    private function _finder(Query $query, $value, ArrayObject $extra)
    {
        $components = [
            '_Session' => $this->request->session()
        ];

        if (!empty($value)) {
            $table = $extra['table'];
            $finders = $this->decode($value);
            foreach ($finders as $name => $options) {
                $options = array_merge($options, $components);
                $finderFunction = 'find' . ucfirst($name);
                if (method_exists($table, $finderFunction) || $table->behaviors()->hasMethod($finderFunction)) {
                    $query->find($name, $options);
                } else {
                    Log::write('debug', 'Finder (' . $finderFunction . ') does not exists.');
                }
            }
            $extra['list'] = array_key_exists('list', $finders);
        }
    }

    private function _contain(Query $query, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $contain = [];
            $table = $extra['table'];

            if ($value === 'true') { // contains all BelongsTo associations
                foreach ($table->associations() as $assoc) {
                    if ($assoc->type() == 'manyToOne') {
                        $contain[] = $assoc->name();
                    }
                }
            } else {
                $contain = explode(',', $value);
            }

            if (!empty($contain)) {
                $query->contain($contain);
                $fields = [];
                foreach ($contain as $name) {
                    foreach ($table->associations() as $assoc) {
                        if ($name == $assoc->name()) {
                            $columns = $assoc->schema()->columns();
                            foreach ($columns as $column) {
                                $fields[] = $assoc->aliasField($column);
                            }
                        }
                    }
                }
                $extra['fields'] = array_merge($extra['fields'], $fields);
            }
        }
    }

    private function _conditions(Query $query, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $conditions = [];
            $table = $extra['table'];
            $columns = $table->schema()->columns();

            foreach ($value as $field => $val) {
                if (in_array($field, $columns)) {
                    $conditions[$table->aliasField($field)] = $val;
                } else {
                    $conditions[$field] = $val;
                }
            }
            $query->where($conditions);
        }
    }

    private function _orWhere(Query $query, $value, ArrayObject $extra)
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
            $query->orWhere([$key => $value]);
        }
    }

    private function _group(Query $query, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $fields = explode(',', $value);
            $query->group($fields);
        }
    }

    private function _order(Query $query, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $fields = explode(',', $value);
            $query->order($fields);
        }
    }

    private function _limit(Query $query, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $extra['limit'] = $value; // used in _page
        }
    }

    private function _page(Query $query, $value, ArrayObject $extra)
    {
        if (!empty($value) && $extra->offsetExists('limit')) {
            $extra['page'] = $value;
        }
    }

    private function convertBinaryToBase64(Entity $entity)
    {
        foreach ($entity->visibleProperties() as $property) {
            if (is_resource($entity->$property)) {
                $entity->$property = base64_encode("data:image/jpeg;base64,".stream_get_contents($entity->$property));
            }
        }
    }

    private function _formatBinaryValue($data) {
        if ($data instanceof Entity) {
            $this->convertBinaryToBase64($data);
        } else {
            foreach ($data as $key => $value) {
                $this->convertBinaryToBase64($value);
            }
        }
        return $data;
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
                $data = $this->_formatBinaryValue($query->all());
                $serialize = ['data' => $data, 'total' => $total];
            }
            $serialize['_serialize'] = array_keys($serialize);
            $this->set($serialize);
        } catch (Exception $e) {
            $this->_outputError($e->getMessage());
        }
    }

    public function add($model)
    {
        $target = $this->_instantiateModel($model);
        if ($target) {
            $entity = $target->newEntity($this->request->data);
            $target->save($entity);
            $this->set([
                'data' => $entity,
                'error' => $entity->errors(),
                '_serialize' => ['data', 'error']
            ]);
        }
    }

    public function view($model, $id)
    {
        $target = $this->_instantiateModel($model);
        if ($target) {
            if ($target->exists([$target->aliasField($target->primaryKey()) => $id])) {
                $requestQueries = $this->request->query;

                $query = $target->find();
                $containments = $this->_setupContainments($target, $requestQueries, $query);

                $fields = [];
                if (!empty($requestQueries)) {
                    $fields = $this->_filterSelectFields($target, $requestQueries, $containments);
                }
                if (is_bool($fields) && !$fields) {
                    $this->_outputError('One or more selected fields do not exists in '.$target->registryAlias());
                } else {
                    if (!empty($fields)) {
                        $query->select($fields);
                    }
                    try {
                        $data = $query->where([$target->aliasField($target->primaryKey()) => $id])->first();
                        $data = $this->_formatBinaryValue($data);
                        $this->_outputData($data);
                    } catch (Exception $e) {
                        $this->_outputError($e->getMessage());
                    }
                }
            } else {
                $this->_outputError('Record does not exists');
            }
        }
    }

    public function edit($model, $id)
    {
        $target = $this->_instantiateModel($model);
        if ($target) {
            if ($target->exists([$target->aliasField($target->primaryKey()) => $id])) {
                $entity = $target->get($id);
                $entity = $target->patchEntity($entity, $this->request->data);
                if (empty($entity->errors())) {
                    $target->save($entity);
                    $this->set([
                        'data' => $entity,
                        'error' => $entity->errors(),
                        '_serialize' => ['data', 'error']
                    ]);
                } else {
                    $this->_outputError($entity->errors());
                }
            } else {
                $this->_outputError('Record does not exists');
            }
        }
    }

    public function delete($model, $id)
    {
        $target = $this->_instantiateModel($model);
        if ($target) {
            if ($target->exists([$target->aliasField($target->primaryKey()) => $id])) {
                $entity = $target->get($id);
                $message = 'Deleted';
                if (!$target->delete($entity)) {
                    $message = 'Error';
                }
                $this->set([
                    'result'=> $message,
                    '_serialize' => ['result']
                ]);
            } else {
                $this->_outputError('Record does not exists');
            }
        }
    }


/***************************************************************************************************************************************************
 *
 * private functions
 *
 ***************************************************************************************************************************************************/

    private function _instantiateModel($model)
    {
        $model = str_replace('-', '.', $model);
        $target = TableRegistry::get($model);
        try {
            $data = $target->find('all')->limit('1');
            return $target;
        } catch (Exception $e) {
            $this->_outputError();
            return false;
        }
    }

    private function _outputError($message = 'Requested Plugin-Model does not exists')
    {
        $model = str_replace('-', '.', $this->request->params['model']);
        $this->set([
            'model' => $model,
            'error' => $message,
            '_serialize' => ['request_method', 'action', 'model', 'error']
        ]);
    }

    private function _outputData($data)
    {
        $this->set([
            'data' => $data,
            '_serialize' => ['data']
        ]);
    }

    private function _setupContainments(Table $target, array $requestQueries, Query $query)
    {
        $contains = [];
        if (array_key_exists('_contain', $requestQueries)) {
            $contains = array_map('trim', explode(',', $requestQueries['_contain']));
            if (!empty($contains)) {
                $trueExists = false;
                foreach ($contains as $key => $contain) {
                    if ($contain=='true') {
                        $trueExists = true;
                        break;
                    }
                }
                if ($trueExists) {
                    foreach ($target->associations() as $assoc) {
                        $contains[] = $assoc->name();
                    }
                }
                $query->contain($contains);
            }
        }
        return $contains;
    }

    private function _filterSelectFields(Table $target, array $requestQueries, array $containments=[])
    {
        $targetColumns = $target->schema()->columns();
        if (!array_key_exists('_fields', $requestQueries)) {
            return [];
        }
        $fields = array_map('trim', explode(',', $requestQueries['_fields']));
        foreach ($fields as $key => $field) {
            if (!in_array($field, $targetColumns)) {
                return false;
            } else {
                $fields[$key] = $target->aliasField($field);
            }
        }
        if (!empty($containments)) {
            foreach ($containments as $key => $name) {
                foreach ($target->associations() as $assoc) {
                    if ($name == $assoc->name()) {
                        $containmentColumns = $assoc->schema()->columns();
                        foreach ($containmentColumns as $containmentColumn) {
                            $fields[] = $assoc->aliasField($containmentColumn);
                        }
                    }
                }
            }
        }
        return $fields;
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
}
