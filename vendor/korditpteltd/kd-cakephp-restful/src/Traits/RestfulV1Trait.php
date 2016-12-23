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
use DateTime;
use DateTimeImmutable;

trait RestfulV1Trait {
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

    private function _fields(Query $query = null, $value, ArrayObject $extra)
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

    private function _finder(Query $query = null, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $table = $extra['table'];
            $finders = $this->decode($value);
            foreach ($finders as $name => $options) {
                $options['_controller'] = $this->controller;
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

    private function _contain(Query $query = null, $value, ArrayObject $extra)
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
                if (!is_null($query)) {
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
            $query->group($fields);
        }
    }

    private function _order(Query $query = null, $value, ArrayObject $extra)
    {
        if (!empty($value)) {
            $fields = explode(',', $value);
            $query->order($fields);
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

    private function convertBinaryToBase64(Table $table, Entity $entity)
    {
        $table = $this->model;
        $schema = $table->schema();

        foreach ($entity->visibleProperties() as $property) {
            if (in_array($schema->columnType($property), ['datetime', 'date'])) {
                if (!empty($entity->$property) && ($entity->$property instanceof DateTime || $entity->$property instanceof DateTimeImmutable)) {
                    $entity->$property = $entity->$property->format('Y-m-d');
                } else if ($entity->$property == '0000-00-00') {
                    $entity->$property = '1970-01-01';
                }
            }
            if ($entity->$property instanceof Entity) {
                $source = $entity->$property->source();
                $entityTable = TableRegistry::get($source);
                $this->convertBinaryToBase64($entityTable, $entity->$property);
                // $this->convertBinaryToBase64($table, $entity->$property);
            } else if (is_resource($entity->$property)) {
                $entity->$property = base64_encode(stream_get_contents($entity->$property));
            } else if ($property == 'password') { // removing password from entity so that the value will not be exposed
                $entity->unsetProperty($property);
            } else {
                $eventKey = 'Restful.Model.onRender' . Inflector::camelize($property);
                $event = $table->dispatchEvent($eventKey, [$entity], $this);
                if ($event->result) {
                    $entity->$property = $event->result;
                }
            }
        }
    }

    private function convertBase64ToBinary(Entity $entity)
    {
        $table = $this->model;
        $schema = $table->schema();
        $columns = $schema->columns();

        foreach ($columns as $column) {
            $attr = $schema->column($column);
            if ($attr['type'] == 'binary' && $entity->has($column)) {
                $value = urldecode($entity->$column);
                $entity->$column = base64_decode($value);
            }
        }
        return $entity;
    }

    private function formatResultSet(Table $table, $data)
    {
        if ($data instanceof Entity) {
            $this->convertBinaryToBase64($table, $data);

        } else {
            foreach ($data as $key => $value) {
                if ($value instanceof Entity) {
                    $this->convertBinaryToBase64($table, $value);
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
                    $contains = [];
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
                if ($addAlias) {
                    $idKeys[$model->aliasField($primaryKey)] = $ids[$primaryKey];
                } else {
                    $idKeys[$primaryKey] = $ids[$primaryKey];
                }
            }
        }
        return $idKeys;
    }

    private function editEntity(Table $table, array $primaryKey, array $data)
    {
        $entity = $table->get($primaryKey);
        $entity = $table->patchEntity($entity, $data);
        $entity = $this->convertBase64ToBinary($entity);
        $table->save($entity);
        $this->controller->set([
            'data' => $entity,
            'error' => $entity->errors(),
            '_serialize' => ['data', 'error']
        ]);
    }

    private function viewEntity(Table $table, array $primaryKey)
    {
        $queryString = $this->request->query;
        $flatten = false;
        $extra = new ArrayObject(['table' => $table, 'fields' => []]);
        $query = null;
        foreach ($queryString as $key => $attr) {
            $this->$key($query, $attr, $extra);
        }
        if (empty($extra['fields'])) {
            unset($extra['fields']);
        }
        if (isset($extra['flatten']) && $extra['flatten'] === true) {
            $flatten = true;
        }
        $data = $table->get($primaryKey, $extra->getArrayCopy());
        $data = $this->formatResultSet($table, $data);
        if ($flatten) {
            $data = Hash::flatten($data->toArray());
        }
        $this->_outputData($data);
    }
}