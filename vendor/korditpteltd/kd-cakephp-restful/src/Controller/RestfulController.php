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

    public $components = [
        'RequestHandler'
    ];
<<<<<<< HEAD

    public function initialize()
    {
        parent::initialize();
    }
=======
>>>>>>> fe4ad9bb870f240ba147ce6aec1faf6ad1ac0730

    public function initialize()
    {
        parent::initialize();
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

<<<<<<< HEAD
        $pass = $this->request->params['pass'];
        if (count($pass) > 0) {
            $model = $this->_instantiateModel($pass[0]);
=======
        if (isset($this->request->model)) {
            $tableAlias = $this->request->model;
            $model = $this->_instantiateModel($tableAlias);
>>>>>>> fe4ad9bb870f240ba147ce6aec1faf6ad1ac0730
            if ($model != false) {
                $this->model = $model;
            }
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

<<<<<<< HEAD
    private function processFinders(Table $table, array $finders)
    {
        $query = $table->find();
        if (!empty($finders)) {
            foreach ($finders as $name => $options) {
                $query->find($name, $options);
            }
        }
        return $query;
    }

    // to convert string into json string
    private function decode($requestQueries, $key)
    {
        $finders = [];
        if (array_key_exists($key, $requestQueries)) {
            $queryArray = explode(',', $requestQueries[$key]);

            foreach ($queryArray as $finder) {
                // to convert to a proper json string for decoding into php array
                $finder = str_replace(':', '":"', $finder);
                $finder = str_replace('[', '":{"', $finder);
                $finder = str_replace(']', '"}}', $finder);
                $finder = '{"' . str_replace(';', '","', $finder);
                
                $noAttributesFound = strripos($finder, '"}') === false;
                if ($noAttributesFound) {
                    $finder .= '": {}}';
                }
                $array = json_decode($finder, true);

                $finders = array_merge($finders, $array);
            }
        }
        return $finders;
    }

    private function processGroupBy(Query $query, $requestQueries)
    {
        if (array_key_exists('_group', $requestQueries)) {
            $group = $requestQueries['_group'];
            $json = '"' . str_replace(',', '","', $group) . '"';
            $groupBy = json_decode($json, true);
            $query->group($groupBy);
        }
        return $query;
=======
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
        if (!empty($value)) {
            $finders = $this->decode($value);
            foreach ($finders as $name => $options) {
                $query->find($name, $options);
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

    private function _group(Query $query, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $fields = explode(',', $value);
            $query->group($fields);
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
>>>>>>> fe4ad9bb870f240ba147ce6aec1faf6ad1ac0730
    }

    public function index()
    {
        if (is_null($this->model)) {
            return;
        }

        $table = $this->model;
<<<<<<< HEAD
        $requestQueries = $this->request->query;

        $finders = $this->decode($requestQueries, '_finder');
        $query = $this->processFinders($table, $finders);
        $listOnly = array_key_exists('list', $finders);

        if (array_key_exists('_finder', $requestQueries)) {
            $this->_attachFieldSpecificFinders($table, $requestQueries, $query);
            $this->_attachFinders($table, $requestQueries, $query);
        }

        $containments = $this->_setupContainments($table, $requestQueries, $query);

        $conditions = [];
        if (!empty($requestQueries)) {
            $conditions = $this->_setupConditions($table, $requestQueries);
        }
        $fields = [];
        if (!empty($requestQueries)) {
            $fields = $this->_filterSelectFields($table, $requestQueries, $containments);
        }
        if (is_bool($conditions) && !$conditions) {
            $this->_outputError('Extra query parameters declared do not exists in '.$table->registryAlias());
        } else if (is_bool($fields) && !$fields) {
            $this->_outputError('One or more selected fields do not exists in '.$table->registryAlias());
        } else {
            if (!empty($conditions)) {
                $query->where($conditions);
            }
            if (!empty($fields)) {
                $query->select($fields);
            }

            $limit = 30;
            $page = 1;
            if (array_key_exists('_limit', $requestQueries)) {
                $limit = $requestQueries['_limit'];
                if (array_key_exists('_page', $requestQueries)) {
                    $page = $requestQueries['_page'];
                }
            }
            if ($limit > 0) {
                $query->limit($limit)->page($page);
            }
            $this->processGroupBy($query, $requestQueries);

            try {
                $data = [];
                if ($listOnly) {
                    $data = $query->toArray();
                } else {
                    $data = $this->_formatBinaryValue($query->all());
                }
                $this->_outputData($data);
            } catch (Exception $e) {
                $this->_outputError($e->getMessage());
            }
        }
    }

    public function add($model) {
=======
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
>>>>>>> fe4ad9bb870f240ba147ce6aec1faf6ad1ac0730
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

<<<<<<< HEAD
    public function view($model, $id) {
=======
    public function view($model, $id)
    {
>>>>>>> fe4ad9bb870f240ba147ce6aec1faf6ad1ac0730
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

<<<<<<< HEAD
    public function edit($model, $id) {
=======
    public function edit($model, $id)
    {
>>>>>>> fe4ad9bb870f240ba147ce6aec1faf6ad1ac0730
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

<<<<<<< HEAD
    public function delete($model, $id) {
=======
    public function delete($model, $id)
    {
>>>>>>> fe4ad9bb870f240ba147ce6aec1faf6ad1ac0730
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

<<<<<<< HEAD
    private function _outputError($message = 'Requested Plugin-Model does not exists') {
=======
    private function _outputError($message = 'Requested Plugin-Model does not exists')
    {
>>>>>>> fe4ad9bb870f240ba147ce6aec1faf6ad1ac0730
        $model = str_replace('-', '.', $this->request->params['model']);
        $this->set([
            'model' => $model,
            'error' => $message,
            '_serialize' => ['request_method', 'action', 'model', 'error']
        ]);
    }

<<<<<<< HEAD
    private function _outputData($data) {
=======
    private function _outputData($data)
    {
>>>>>>> fe4ad9bb870f240ba147ce6aec1faf6ad1ac0730
        $this->set([
            'data' => $data,
            '_serialize' => ['data']
        ]);
    }

<<<<<<< HEAD
    private function _formatBinaryValue($data) {
        if ($data instanceof Entity) {
            foreach ($data->visibleProperties() as $property) {
                if (is_resource($data->$property)) {
                    $data->$property = base64_encode("data:image/jpeg;base64,".stream_get_contents($data->$property));                      
                }
            }
        } else {
            foreach ($data as $key => $value) {
                foreach ($value->visibleProperties() as $property) {
                    if (is_resource($value->$property)) {
                        $value->$property = base64_encode("data:image/jpeg;base64,".stream_get_contents($value->$property));                        
                    }
                }
            }
        }
        return $data;
    }

    private function _parseFindByList(Table $target, array $requestQueries) {
        $finders = explode(',', $requestQueries['_finder']);
        foreach ($finders as $key => $finder) {
            if (substr_count($finder, 'list')>0) {

                $bracketPost = strpos($finder, '[');
                if ($bracketPost>0) {
                    $parameters = substr($finder, $bracketPost+1, -1);
                    $parameters = explode(';', $parameters);
                } else {
                    $parameters = [];
                }

                $keyField = $target->primaryKey();
                $valueField = $target->displayField();
                $groupField = null;
                if (isset($parameters[0]) && !empty($parameters[0])) {
                    $keyField = $parameters[0];
                }
                if (isset($parameters[1]) && !empty($parameters[1])) {
                    $valueField = $parameters[1];
                }
                if (isset($parameters[2]) && !empty($parameters[2])) {
                    $groupField = $parameters[2];
                }
                return $target->find('list', [
                        'keyField' => $keyField,
                        'valueField' => $valueField,
                        'groupField' => $groupField
                    ]);

            }
        }
    }
    
    private $_specificFields = ['visible', 'active', 'order', 'editable'];
    private function _attachFieldSpecificFinders(Table $target, array $requestQueries, Query $query) {
        $finders = explode(',', $requestQueries['_finder']);
        foreach ($finders as $key => $finder) {
            $strlen = (strpos($finder, '[')>0) ? strpos($finder, '[') : strlen($finder);
            $functionName = strtolower(substr($finder, 0, $strlen));
            if (in_array($functionName, $this->_specificFields)) {
                $targetColumns = $target->schema()->columns();
                if (in_array($functionName, $targetColumns)) {
                    $parameters = $this->_setupFinderParams($finder);
                    if (method_exists($target, 'find'.ucwords($functionName))) {
                        $query->find($functionName, $parameters);
                    }
                }
            }
        }
        return $query;
    }
    
    private function _attachFinders(Table $target, array $requestQueries, Query $query) {
        $finders = explode(',', $requestQueries['_finder']);
        foreach ($finders as $key => $finder) {
            $strlen = (strpos($finder, '[')>0) ? strpos($finder, '[') : strlen($finder);
            $functionName = strtolower(substr($finder, 0, $strlen));
            if (!in_array($functionName, array_merge($this->_specificFields, ['list']))) {
                $parameters = $this->_setupFinderParams($finder);
                if (method_exists($target, 'find'.ucwords($functionName))) {
                    $query->find($functionName, $parameters);
                } else {
                    foreach ($target->behaviors()->loaded() as $behaviorName) {
                        $behavior = $target->behaviors()->get($behaviorName);
                        if (method_exists($behavior, 'find'.ucwords($functionName))) {
                            $query->find($functionName, $parameters);
                        }
                    }
                }
            }
        }
        return $query;
    }

    private function _setupFinderParams($finder) {
        $strlen = (strpos($finder, '[')>0) ? strpos($finder, '[') : strlen($finder);
        $parameters = substr($finder, $strlen+1, -1);
        if (!empty($parameters)) {
            $parameters = explode(';', $parameters);
            foreach ($parameters as $key => $value) {
                $buffer = explode(':', $value);
                $parameters[$buffer[0]] = $buffer[1];
            }
        } else {
            $parameters = [];
        }
        return $parameters;
    }
    
    private $_specialParams = ['_finder', '_limit', '_page', '_fields', '_contain'];
    private function _setupConditions(Table $target, array $requestQueries) {
        $targetColumns = $target->schema()->columns();
        $conditions = [];
        foreach ($requestQueries as $requestQueryKey => $requestQuery) {
            if (in_array($requestQueryKey, $this->_specialParams)) {
                continue;
            }
            if (!in_array($requestQueryKey, $targetColumns)) {
                return false;
            }
            $conditions[$target->aliasField($requestQueryKey)] = $requestQuery;
        }
        return $conditions;
    }

    private function _setupContainments(Table $target, array $requestQueries, Query $query) {
=======
    private function _setupContainments(Table $target, array $requestQueries, Query $query)
    {
>>>>>>> fe4ad9bb870f240ba147ce6aec1faf6ad1ac0730
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

<<<<<<< HEAD
    private function _filterSelectFields(Table $target, array $requestQueries, array $containments=[]) {
=======
    private function _filterSelectFields(Table $target, array $requestQueries, array $containments=[])
    {
>>>>>>> fe4ad9bb870f240ba147ce6aec1faf6ad1ac0730
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
<<<<<<< HEAD
=======

    private function startsWith($haystack, $needle)
    {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }
>>>>>>> fe4ad9bb870f240ba147ce6aec1faf6ad1ac0730
}
