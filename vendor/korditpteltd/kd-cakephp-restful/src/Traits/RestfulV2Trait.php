<?php
namespace Restful\Traits;

use Exception;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;
use Cake\Utility\Inflector;
use Cake\Utility\Hash;
use Cake\Chronos\MutableDate;
use Cake\Chronos\Chronos;
use Cake\Chronos\Date;
use Cake\Chronos\MutableDateTime;

trait RestfulV2Trait {

    private function initTable(Table $table, $connectionName = 'default')
    {
        $_connectionName = $this->request->query('_db') ? $this->request->query('_db') : $connectionName;
        if (method_exists($table, 'setConnectionName')) {
           $table::setConnectionName($_connectionName);
        }
        return $table;
    }

    private function processQueryString($requestQueries, Query $query = null, ArrayObject $extra)
    {
        $searchableFields = [];
        $table = $extra['table'];
        if (isset($requestQueries['_action'])) {
            $extra['action'] = strtolower($requestQueries['_action']);
        }
        $requestQuery = new ArrayObject($requestQueries);
        $table->dispatchEvent('Restful.Model.onBeforeProcessQueryString', [$requestQuery, $query, $extra], $this->controller);
        $requestQueries = $requestQuery->getArrayCopy();
        foreach ($table->schema()->columns() as $column) {
            $attr = $table->schema()->column($column);
            if ($attr['type'] == 'string' || $attr['type'] == 'text') {
                $searchableFields[] = $table->aliasField($column);
            }
        }
        $extra['searchableFields'] = $searchableFields;
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

        $default = ['_limit' => 30, '_page' => 1];
        $queryString = array_merge($default, $requestQueries);
        foreach ($queryString as $key => $attr) {
            if (method_exists($this, $key)) {
               $this->$key($query, $attr, $extra);
            }
        }
        if (array_key_exists('_fields', $queryString) && !empty($extra['fields'])) {
            if (!is_null($query)) {
                $query->select($extra['fields']);
            }
        }
    }

    private function processSchema(Table $table, ArrayObject $extra)
    {
        $schema = [];
        $action = $extra['action'];
        $columns = $table->schema()->columns();

        // Only return the selected fields
        if ($extra->offsetExists('schema_fields')) {
            if ($extra['schema_fields'] && is_array($extra['schema_fields'])) {
                $columns = array_intersect($columns, $extra['schema_fields']);
            }
        }

        // Virtual fields or visible hidden properties
        $newEntity = $table->newEntity();
        $visibleProperties = $newEntity->visibleProperties();
        $columns = array_merge($columns, $visibleProperties);

        $columnsObject = new ArrayObject($columns);
        $table->dispatchEvent('Restful.Model.onBeforeAction', [$action, $columnsObject, $extra], $this->controller);
        $columns = $columnsObject->getArrayCopy();

        $columnsAttributes = new ArrayObject([]);

        foreach ($columns as $col) {
            $attr = is_array($table->schema()->column($col)) ? $table->schema()->column($col) : [];
            $attr = new ArrayObject($attr);
            if (empty($attr->getArrayCopy())) {
                $attr = new ArrayObject([
                    'type' => 'string',
                    'null' => true,
                    'autoIncrement' => false,
                    'visible' => true
                ]);
            }
            $table->dispatchEvent('Restful.Model.onSetupFieldAttributes', [$col, $attr, $extra], $this->controller);
            $columnsAttributes[$col] = $attr->getArrayCopy();
        }

        $event = $table->dispatchEvent('Restful.Model.onSetupFields', [$columnsAttributes, $extra], $this->controller);
        return $columnsAttributes;
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

    private function _fields(Query $query = null, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $table = $extra['table'];
            $columns = $table->schema()->columns();

            $fields = explode(',', $value);
            $extra['schema_fields'] = $fields;
            foreach ($fields as $index => $field) {
                if (strpos($field, ':')) {
                    list($alias, $value) = explode(':', $field);
                    $fields[$alias] = $value;
                    unset($fields[$index]);
                } else if (in_array($field, $columns)) {
                    $fields[$index] = $table->aliasField($field);
                }
            }
            $extra['fields'] = array_merge($extra['fields'], $fields);
        }
    }

    private function _finder(Query $query = null, $value, ArrayObject $extra)
    {
        $extra['finder'] = [];
        if (!empty($value)) {
            $table = $extra['table'];
            $finders = $this->decode($value);
            foreach ($finders as $name => $options) {
                $options['_controller'] = $this->controller;
                $extra['finder'][] = $name;
                $finderFunction = 'find' . ucfirst($name);
                if ($table->hasFinder($name)) {
                    if (!is_null($query)) {
                        $options['extra'] = $extra;
                        $query->find($name, $options);
                    }
                } else {
                    Log::write('debug', 'Finder (' . $finderFunction . ') does not exists.');
                }
            }
            $extra['list'] = array_key_exists('list', $finders);
        }
    }

    private function _schema(Query $query = null, $value, ArrayObject $extra)
    {
        $extra['showSchema'] = $value;
    }

    private function _innerJoinWith(Query $query = null, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $innerJoinAssoc = [];
            $table = $extra['table'];

            if (strpos($value, ',')) {
                $innerJoinAssoc[] = $value;
            } else {
                $innerJoinAssoc = explode(',', $value);
            }

            if (!empty($innerJoinAssoc)) {
                if (!is_null($query)) {
                    foreach ($innerJoinAssoc as $joinAssoc) {
                        $query->innerJoinWith($joinAssoc);
                    }
                }
            }

            $extra['innerJoinWith'] = $innerJoinAssoc;
        }
    }

    private function _contain(Query $query = null, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $contain = [];
            $table = $extra['table'];

            $valueArr = explode(',', $value);
            foreach ($valueArr as $item) {
                if ($item === 'true') { // contains all BelongsTo associations
                    foreach ($table->associations() as $assoc) {
                        if ($assoc->type() == 'manyToOne') {
                            $contain[] = $assoc->name();
                        }
                    }
                } else {
                    $contain[] = $item;
                }
            }

            if (!empty($contain)) {
                if (!is_null($query)) {
                    $query->contain($contain);
                }
            }
            $extra['contain'] = $contain;
        }
    }

    private function _conditions(Query $query = null, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $conditions = [];
            $table = $extra['table'];
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

    private function _orWhere(Query $query = null, $value, ArrayObject $extra)
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

    private function _group(Query $query = null, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $fields = explode(',', $value);
            if (!is_null($query)) {
                $query->group($fields);
            }
        }
    }

    private function _order(Query $query = null, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $fields = explode(',', $value);
            if (!is_null($query)) {
                $query->order($fields);
            }
        }
    }

    private function _limit(Query $query = null, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $extra['limit'] = $value; // used in _page
        }
    }

    private function _page(Query $query = null, $value, ArrayObject $extra)
    {
        if (!empty($value) && $extra->offsetExists('limit')) {
            $extra['page'] = $value;
        }
    }

    private function _flatten(Query $query = null, $value, ArrayObject $extra)
    {
        if ($value == true) {
            $extra['flatten'] = true;
        }
    }

    private function _showBlobContent(Query $query = null, $value, ArrayObject $extra)
    {
        $extra['blobContent'] = $value;
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
        } else if ($attribute == '0000-00-00') {
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

    private function formatResultSet(Table $table, $data, $extra)
    {
        if ($data instanceof Entity) {
            $this->convertBinaryToBase64($table, $data, $extra);
        } else if (is_array($data)) {
            foreach ($data as $key => $value) {
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
        $this->controller->set([
            'model' => $model,
            'error' => $message,
            '_serialize' => ['request_method', 'action', 'model', 'error']
        ]);
    }

    private function _outputData($data)
    {
        $this->controller->set([
            'data' => $data,
            '_serialize' => ['data']
        ]);
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

    private function getIdKeys(Table $model, $ids, $addAlias = true)
    {
        $primaryKey = $model->primaryKey();
        $idKeys = [];
        if (!empty($ids)) {
            if (is_array($primaryKey)) {
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
}
