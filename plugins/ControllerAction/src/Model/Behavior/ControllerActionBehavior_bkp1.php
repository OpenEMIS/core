<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\EventInterface;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Log\Log;
use Cake\Http\ServerRequest;
use Cake\Http\Session;

use ControllerAction\Model\Traits\EventTrait;

class ControllerActionBehavior extends Behavior
{
    use EventTrait;

    protected $_defaultConfig = [
        'actions' => [
            'index' => true,
            'add' => true,
            'view' => true,
            'edit' => true,
            'remove' => 'cascade',
            'search' => ['orderField' => 'order'],
            'reorder' => ['orderField' => 'order'],
            'download' => ['show' => false, 'name' => 'file_name', 'content' => 'file_content']
        ],
        'fields' => [
            'excludes' => ['modified', 'created']
        ]
    ];

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

    public function initialize(array $config): void
    {
        $this->attachActions();
        $this->initializeFields();
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();

        // Model default priority is 10
        // $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction', 'priority' => 5];
        return $events;
    }

    public function buildValidator(EventInterface $event, Validator $validator, $name)
    {
        if ($name == 'default') {
            $schema = $this->_table->getSchema();

            $columns = $schema->columns();
            foreach ($columns as $col) {
                $attr = $schema->getColumn($col);

                if ($validator->hasField($col)) {
                    $set = $validator->field($col);

                    if (!$set->isEmptyAllowed()) {
                        $set->add('notBlank', ['rule' => 'notBlank']);
                    }
                    if (!$set->isPresenceRequired()) {
                        if ($this->isForeignKey($col)) {
                            $validator->requirePresence($col);
                        }
                    }
                } else {
                    if (isset($attr['null'])) {
                        $ignoreFields = $this->getConfig('fields.excludes');
                        if ($attr['null'] === false // not nullable
                            && (isset($attr['default']) && strlen($attr['default']) == 0) // don't have a default value in database
                            && $col !== 'id' // not a primary key
                            && !in_array($col, $ignoreFields) // fields not excluded
                        ) {
                            $validator->add($col, 'notBlank', ['rule' => 'notBlank']);
                            if ($this->isForeignKey($col)) {
                                $validator->requirePresence($col);
                            }
                        }
                    }
                }
            }
        }
    }

    public function excludeDefaultValidations($fields)
    {
        if (!empty($fields)) {
            $this->getConfig('fields.excludes', $fields);
        }
    }

    private function attachActions()
    {
        $actions = $this->getConfig('actions');
        $model = $this->_table;

        foreach ($this->_defaultConfig['actions'] as $action => $value) {
            if ($actions[$action] !== false) {
                $behavior = ucfirst($action);
                if (file_exists(__DIR__ . DS . $behavior . 'Behavior.php')) {
                    if ($action == 'reorder' && !$this->isColumnExists($value['orderField'])) {
                        $this->getConfig('actions.reorder', false);
                        continue;
                    }
                    if (is_array($value)) {
                        if ($model->hasBehavior($behavior)) {
                            $model->removeBehavior($behavior);
                            // Log::write('debug', 'Conflicts in behavior: ' . $behavior);
                        }
                        $model->addBehavior('ControllerAction.' . $behavior, $value);
                    } else {
                        $model->addBehavior('ControllerAction.' . $behavior);
                    }
                }
            }
        }
    }

    private function initializeFields()
    {
        $alias = $this->_table->getAlias();
        $className = $this->_table->getRegistryAlias();
        $schema = $this->_table->getSchema();

        $columns = $schema->columns();
        $fields = [];
        $visibility = ['view' => true, 'edit' => true, 'index' => true];
        $order = 10;

        foreach ($columns as $i => $col) {
            $attr = $schema->getColumn($col);
            $attr['model'] = $alias;
            $attr['className'] = $className;
            $attr['visible'] = $col != 'password' ? $visibility : false;
            $attr['order'] = $order * ($i+1);
            $attr['field'] = $col;

            $fields[$col] = $attr;
        }
        $primaryKey = $this->_table->getPrimaryKey();

        if (!is_array($primaryKey) && array_key_exists($primaryKey, $fields)) { // not composite primary keys
            $fields[$primaryKey]['type'] = 'hidden';
        } else {
            if (isset($fields['id'])) {
                $fields['id']['type'] = 'hidden';
            }
            foreach ($primaryKey as $value) {
                $fields[$value]['type'] = 'hidden';
            }
        }

        $excludedFields = $this->getConfig('fields.excludes');
        foreach ($excludedFields as $field) {
            if (array_key_exists($field, $fields)) {
                $fields[$field]['visible']['index'] = false;
                $fields[$field]['visible']['view'] = true;
                $fields[$field]['visible']['edit'] = false;
                $fields[$field]['labelKey'] = 'general';
            }
        }

        $this->_table->fields = $fields;
    }

    public function isColumnExists($field)
    {
        $model = $this->_table;
        $schema = $model->getSchema();
        $columns = $schema->columns();

        return in_array($field, $columns);
    }

    public function actions($action = null)
    {
        $actions = $this->getConfig('actions');

        $data = false;
        if (is_null($action)) {
            foreach ($actions as $key => $enabled) {
                if ($enabled !== false) {
                    $data[$key] = $enabled;
                }
            }
        } else {
            if (array_key_exists($action, $actions)) {
                $data = $actions[$action];
            }
        }
        return $data;
    }

    public function setDeleteStrategy($strategy)
    {
        $strategies = ['cascade', 'restrict'];
        if (in_array($strategy, $strategies)) {
            $this->getConfig('actions.remove', $strategy);
        }
    }

    public function paramsPass($index = null)
    {
        $params = $this->_table->request->getParam('pass');
        if (count($params) > 0) {
            if (!is_numeric($params[0])) {
                array_shift($params);
            }
            if (!is_null($index)) {

                if (isset($params[$index])) {
                    $params = $params[$index];
                } else {

                    $params = null;
                }
            }
        }

        return $params;
    }

    public function paramsQuery()
    {
        return $this->_table->request->getQuery();
    }

    public function params()
    {
        $params = $this->paramsPass();
        return array_merge($params, $this->paramsQuery());
    }

    public function toggle($action, $enabled)
    {
        $actions = $this->getConfig('actions');

        if (array_key_exists($action, $actions)) {
            $flag = $actions[$action];
            if ($flag != $enabled) {
                if ($enabled) {
                    $this->_table->addBehavior('ControllerAction.' . ucfirst($action));
                } else {
                    $this->_table->addBehavior('remove',['enabled'=>ucfirst($action)]);
                }
                $actions[$action] = $enabled;
            }
        } else {
            Log::write('debug', __METHOD__ . ': ' . $action . ' does not exists!');
        }
        $this->getConfig('actions', $actions);
    }

    private function mergeRequestParams(array &$url)
    {
        //comment cakephp4
        //$requestParams =$this->_table->request->params;
        $requestParams = $this->table()->request->getAttribute('params');
        foreach ($requestParams as $key => $value) {
            if (is_numeric($key) || in_array($key, $this->cakephpReservedPassKeys)) {
                unset($requestParams[$key]);
            }
        }
        $url = array_merge($url, $requestParams);
        return $url;
    }

    public function url($action, $params = true /* 'PASS' | 'QUERY' | false */)
    {
        $controller = $this->table()->controller;
        $url = [
            'plugin' => $controller->getPlugin(),
            'controller' => $controller->getName(),
            'action' => $this->_table->getAlias(),
            0 => $action
        ];

        $this->mergeRequestParams($url);

        if ($params === true) {
            $url = array_merge($url, $this->params());

        } elseif ($params === 'PASS') {
            $url = array_merge($url, $this->paramsPass());
        } elseif ($params === 'QUERY') {
            $url = array_merge($url, $this->paramsQuery());
        }
       // print_r($url);die('dgd');
        return $url;
    }

    public function getUrlParams($action, $hash)
    {
        $model = $this->_table;
        $session = $model->request->session();
        $sessionKey = 'Url.params.' . implode('.', $action) . '.' . $hash;
        $params = $session->read($sessionKey);
        return $params;
    }

    public function setUrlParams($action, $params = [])
    {
        $session = $this->_table->request->session();
        $hash = sha1(time());
        $sessionKey = 'Url.params.' . implode('.', $action);
        $session->delete($sessionKey);
        $session->write($sessionKey . '.' . $hash, $params);
        $action['hash'] = $hash;
        return $action;
    }

    public function field($name, $attr = [])
    {
        $model = $this->_table;

        if (!isset($model->fieldOrder)) {
            $model->fieldOrder = 1;
        }
        $order = $model->fieldOrder++;

        if (isset($attr['after'])) {
            $after = $attr['after'];
            $order = $this->getOrderValue($after, 'after');
        } elseif (isset($attr['before'])) {
            $before = $attr['before'];
            $order = $this->getOrderValue($before, 'before');
        }
        if ($order == false) {
            $order = $model->fieldOrder - 1;
        }

        $_attr = [
            'type' => 'string',
            'null' => true,
            'autoIncrement' => false,
            'visible' => true,
            'field' => $name,
            'model' => $model->getAlias(),
            'className' => $model->getRegistryAlias()
        ];

        if (array_key_exists($name, $model->fields)) {
            $_attr = array_merge($_attr, $model->fields[$name]);
        }

        $attr = array_merge($_attr, $attr);
        $attr['order'] = $order;
        $model->fields[$name] = $attr;

        $method = 'onUpdateField' . Inflector::camelize($name);
        $eventKey = 'ControllerAction.Model.' . $method;
        $serverRequest = new ServerRequest();
        $params = [$attr, $model->action, $serverRequest, /*$model->request*/];
        $event = $this->dispatchEvent($model, $eventKey, $method, $params, true);
        if (is_array($event->getResult())) {
            $model->fields[$name] = $event->getResult();
        }
    }

    public function setFieldVisible($actions, $fields)
    {
        foreach ($this->_table->fields as $key => $attr) {
            if (in_array($key, $fields)) {
                foreach ($actions as $action) {
                    if (is_array($this->_table->fields[$key]['visible'])) {
                        $this->_table->fields[$key]['visible'][$action] = true;
                    } else {
                        $this->_table->fields[$key]['visible'] = [$action => true];
                    }
                }
            } else {
                $this->_table->fields[$key]['visible'] = false;
            }
        }
    }

    public function setFieldOrder($field, $order = 0)
    {
        $fields = $this->_table->fields;

        if (is_array($field)) {
            foreach ($field as $key) {
                if (array_key_exists($key, $fields)) {
                    $fields[$key]['order'] = $order++;
                }
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
            // uasort($fields, [$this, 'sortFields']);
        }
        $this->_table->fields = $fields;
    }

    public function isForeignKey($field)
    {
        $model = $this->_table;
        foreach ($model->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // belongsTo associations
                if ($field === $assoc->getForeignKey()) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getAssociatedModel($field, $type = 'belongsTo' /* hasOne | hasMany | belongsToMany */)
    {
        $associationTypes = [
            'belongsTo' => 'manyToOne',
            'hasMany' => 'oneToMany',
            'belongsToMany' => 'manyToMany'
        ];
        $model = null;

        foreach ($this->_table->associations() as $assoc) {
            if ($assoc->type() == $associationTypes[$type]) { // belongsTo associations
                if ($field === $assoc->getForeignKey()) {
                    $model = $assoc;
                    break;
                } elseif (is_array($assoc->getForeignKey()) && in_array($field, $assoc->foreignKey())) {
                    $model = $assoc;
                    break;
                }
            }
        }
        return $model;
    }

    public function getAssociatedEntity($field)
    {
        $associationKey = $this->getAssociatedModel($field);
        $associatedEntity = null;
        if (is_object($associationKey)) {
            $associatedEntity = Inflector::underscore(Inflector::singularize($associationKey->getAlias()));
        } else {
            // die($field . '\'s association not found in ' . $this->model->alias());
            Log::write('debug', $field . '\'s association not found in ' . $this->_table->alias());
        }
        return $associatedEntity;
    }

    public function getSearchKey()
    {
        $request = new ServerRequest();
        $session = new Session();
        $session = $request->getSession();
        // $session = $this->_table->request->session();
        return $session->read($this->_table->getRegistryAlias().'.search.key');
    }

    public function getContains($type = 'belongsTo', ArrayObject $extra)
    {
 // type is not being used atm
        $model = $this->_table;
        $contain = [];
        $containFields = [];

        if (isset($extra['auto_contain_fields'])) {
            $containFields = $extra['auto_contain_fields'];
        }

        foreach ($model->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // only contain belongsTo associations
                $fields = [];
                if (array_key_exists($assoc->getName(), $containFields)) {
                    $fields = $containFields[$assoc->getName()];
                }
                $columns = $assoc->getSchema()->columns();
                if (in_array('name', $columns)) {
                    $fields = array_merge($fields, ['id', 'name']);
                    foreach ($columns as $col) {
                        if ($this->endsWith($col, '_id')) {
                            $fields[] = $col;
                        }
                    }
                    $contain[$assoc->getName()] = ['fields' => $fields];
                } elseif (in_array($assoc->getName(), ['ModifiedUser', 'CreatedUser'])) {
                    $contain[$assoc->getName()] = ['fields' => ['id', 'first_name', 'last_name']];
                } else {
                    $contain[$assoc->getName()] = [];
                }
            }
        }
        return $contain;
    }

    public function endsWith($haystack, $needle)
    {
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

    private function getOrderValue($field, $insert)
    {
        $model = $this->_table;
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

    private function _sortByOrder($a, $b)
    {
        if (!isset($a['order']) && !isset($b['order'])) {
            return true;
        } elseif (!isset($a['order']) && isset($b['order'])) {
            return true;
        } elseif (isset($a['order']) && !isset($b['order'])) {
            return false;
        } else {
            return $a["order"] - $b["order"];
        }
    }

    public function getPrimaryKey()
    {
        $primaryKey = $this->_table->getPrimaryKey();
        if (is_array($primaryKey)) {
            $primaryKey = 'id';
        }
        return $primaryKey;
    }
}
