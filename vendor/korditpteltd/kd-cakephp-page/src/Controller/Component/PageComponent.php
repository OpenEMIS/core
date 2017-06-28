<?php
namespace Page\Controller\Component;

use ArrayObject;
use Exception;

use Cake\Core\Configure;
use Cake\Chronos\MutableDate;
use Cake\Chronos\Chronos;
use Cake\Chronos\Date;
use Cake\Chronos\MutableDateTime;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;
use Cake\Utility\Hash;
use Cake\Controller\Exception\MissingActionException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Controller\Component;

use Page\Model\Entity\PageElement;
use Page\Model\Entity\PageFilter;
use Page\Model\Entity\PageTab;
use Page\Traits\EncodingTrait;

class PageComponent extends Component
{
    use EncodingTrait;

    private $debug = false;
    private $controller = null;
    private $mainTable = null;
    private $order = [];
    private $moveSourceField;
    private $queryOptions;
    private $paginateOptions;
    private $limitOptions = [10 => 10, 20 => 20, 30 => 30, 40 => 40, 50 => 50];
    private $autoContain = true;
    private $autoRender = true;
    private $allowedActions = [
        'index' => true,
        'add' => true,
        'view' => true,
        'edit' => true,
        'delete' => true,
        'search' => true
    ];

    // page elements
    private $showElements = false;
    private $header = '';
    private $elements;
    private $filters;
    private $toolbar;
    private $tabs;

    private $excludedFields = ['order', 'modified', 'modified_user_id', 'created', 'created_user_id'];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->controller = $this->_registry->getController();

        $this->elements = new ArrayObject();
        $this->filters = new ArrayObject();
        $this->queryOptions = new ArrayObject();
        $this->paginateOptions = new ArrayObject(['limit' => 10]);
        $this->toolbar = new ArrayObject();
        $this->tabs = new ArrayObject();
    }

    // Is called after the controller's beforeFilter method but before the controller executes the current action handler.
    public function startup(Event $event)
    {
        $request = $this->request;

        if (empty($request->params['_ext'])) {
            // $request->params['_ext'] = 'json';
        }

        if ($this->getQueryString('limit')) {
            $this->setPaginateOption('limit', $this->getQueryString('limit'));
        }
    }

    // Is called after the controller executes the requested action’s logic, but before the controller renders views and layout.
    public function beforeRender(Event $event)
    {
        $controller = $this->controller;
        $request = $this->request;
        $requestQueries = $request->query;
        $isGet = $request->is(['get']);
        $isAjax = $request->is(['ajax']);

        if ($isGet || $isAjax || $this->showElements()) {
            $controller->set('header', $this->getHeader());
            if ($this->elements->count() > 0) {
                $elements = $this->elementsToJSON();
                $controller->set('elements', $elements);
                // $controller->set('schema', $elements);
            }

            if ($this->filters->count() > 0) {
                $controller->set('filters', $this->filtersToJSON());
            }

            if ($this->toolbar->count() > 0) {
                $controller->set('toolbar', $this->toolbar->getArrayCopy());
            }

            if ($this->tabs->count() > 0) {
                $controller->set('tabs', $this->tabsToArray());
            }

            // $controller->set('allowedActions', $this->allowedActions);

            if ($this->hasMainTable()) {
                $table = $this->getMainTable();
                if (array_key_exists('paging', $request->params)) {
                    $paging = $request->params['paging'][$table->alias()];
                    $paging['limitOptions'] = $this->limitOptions;
                    $controller->set('paging', $paging);
                }
            }
        }

        if ($this->debug) {
            pr($this->controller->viewVars);die;
        }
    }

    public function debug($bool)
    {
        $this->debug = $bool;
    }

    public function disable($actions)
    {
        foreach ($actions as $action) {
            $this->allowedActions[$action] = false;
        }
    }

    public function throwMissingActionException()
    {
        $request = $this->request;
        throw new MissingActionException([
            'controller' => $this->controller->name . "Controller",
            'action' => $request->params['action'],
            'prefix' => isset($request->params['prefix']) ? $request->params['prefix'] : '',
            'plugin' => $request->params['plugin'],
        ]);
    }

    public function isActionAllowed($action)
    {
        return $this->allowedActions[$action];
    }

    public function isAutoContain()
    {
        return $this->autoContain;
    }

    public function setAutoContain($bool)
    {
        $this->autoContain = $bool;
        return $this;
    }

    public function isAutoRender()
    {
        return $this->autoRender;
    }

    public function setAutoRender($bool)
    {
        $this->autoRender = $bool;
        return $this;
    }

    public function autoConditions(Table $table, Query $query, array $querystring)
    {
        $conditions = [];
        $columns = $table->schema()->columns();
        foreach ($querystring as $key => $value) {
            if (in_array($key, $columns)) {
                $conditions[$table->aliasField($key)] = $value;
            }
        }
        if (!empty($conditions)) {
            $query->where($conditions);
        }
    }

    public function getContains(Table $table)
    {
        $contain = [];
        foreach ($table->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // belongsTo associations
                $contain[] = $assoc->name();
            }
        }
        return $contain;
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function setHeader($header)
    {
        $this->header = $header;
        return $this;
    }

    public function getElements()
    {
        return $this->elements;
    }

    public function hasMainTable()
    {
        return !is_null($this->mainTable);
    }

    public function getMainTable()
    {
        return $this->mainTable;
    }

    public function addTab($name)
    {
        $tab = new PageTab();
        $this->tabs->offsetSet($name, $tab);
        return $tab;
    }

    public function getTab($name)
    {
        $tab = null;
        if ($this->tabs->offsetExists($name)) {
            $tab = $this->tabs->offsetGet($name);
        }
        return $tab;
    }

    public function addToToolbar($name, $attr)
    {
        $this->toolbar->offsetSet($name, $attr);
        return $this;
    }

    public function getToolbar()
    {
        return $this->toolbar;
    }

    public function attachPrimaryKey(Table $table, $entity)
    {
        if ($entity instanceof Entity) {
            $primaryKey = $table->primaryKey();

            if (!is_array($primaryKey)) {
                $key = [$primaryKey => $entity->$primaryKey];
                $entity->primaryKey = $this->strToHex(json_encode($key));
            } else {
                pr($primaryKey);die;
            }
        }
    }

    public function defaultSearch(Table $table, Query $query, ArrayObject $options)
    {
        $value = $options['searchText'];
        $types = ['string', 'text'];
        $schema = $table->schema();
        $columns = $schema->columns();
        $wildcard = $options['wildcard'];

        $OR = [];
        foreach ($columns as $name) {
            $columnInfo = $schema->column($name);
            if ($name == 'id' || $name == 'password') continue;

            // if the field is of a searchable type and it is part of the table schema
            if (in_array($columnInfo['type'], $types)) {
                $searchValue = $value;
                if ($wildcard === true) {
                    $searchValue = '%' . $value . '%';
                } elseif ($wildcard == 'left') {
                    $searchValue = '%' . $value;
                } elseif ($wildcard == 'right') {
                    $searchValue = $value . '%';
                }
                $OR[$table->aliasField($name) . ' LIKE'] = $searchValue;
            }
        }

        // may have problems with complicated conditions
        if (!empty($OR)) {
            $query->where(['OR' => $OR]);
        }
    }

    public function getQueryString($key = null)
    {
        $querystring = $this->request->query('querystring');
        if ($querystring) {
            $querystring = json_decode($this->hexToStr($querystring), true);
            if (!is_null($key)) {
                if (array_key_exists($key, $querystring)) {
                    $querystring = $querystring[$key];
                } else {
                    $querystring = false;
                }
            }
        } else {
            $querystring = [];
        }
        return $querystring;
    }

    public function hasSearchText()
    {
        $querystring = $this->getQueryString();
        return array_key_exists('search', $querystring) && strlen($querystring['search']) > 0;
    }

    public function getSearchText()
    {
        $querystring = $this->getQueryString();
        return $querystring['search'];
    }

    public function setQueryOption($key, $value)
    {
        $this->queryOptions->offsetSet($key, $value);
        return $this;
    }

    public function getQueryOptions()
    {
        return $this->queryOptions;
    }

    public function setPaginateOption($key, $value)
    {
        $this->paginateOptions->offsetSet($key, $value);
        return $this;
    }

    public function getPaginateOptions()
    {
        return $this->paginateOptions;
    }

    public function getUrl(array $url = [], $params = true /* 'PASS' | 'QUERY' | false */)
    {
        $controller = $this->controller;
        $request = $this->request;
        $_url = [
            'plugin' => $controller->plugin,
            'controller' => $controller->name
        ];

        $url = array_merge($_url, $url);

        if ($params === true) {
            $url = array_merge($url, $request->pass, $request->query);
        } elseif ($params === 'PASS') {
            $url = array_merge($url, $request->pass);
        } elseif ($params === 'QUERY') {
            $url = array_merge($url, $request->query);
        }
        return $url;
    }

    public function showElements($show = null)
    {
        if (is_null($show)) {
            $requestQueries = $this->request->query;
            if ($this->showElements == false) {
                if (array_key_exists('elements', $requestQueries) && $requestQueries['elements'] == 'true') {
                    return true;
                }
            }
            return $this->showElements;
        } else {
            $this->showElements = $show;
        }
    }

    public function loadElementsFromTable(Table $table)
    {
        $this->mainTable = $table;
        $schema = $table->schema();
        $columns = $schema->columns();

        foreach ($columns as $columnName) {
            if (!in_array($columnName, $this->excludedFields)) {
                $attributes = $schema->column($columnName);
                $foreignKey = $this->isForeignKey($table, $columnName);
                $attributes['foreignKey'] = $foreignKey;
                $attributes['model'] = $table->alias();
                $element = new PageElement($columnName, $attributes);

                // setup displayFrom
                if ($foreignKey) {
                    $belongsTo = $table->{$foreignKey['name']};
                    $entity = $belongsTo->newEntity();

                    $columns = array_merge($entity->visibleProperties(), $belongsTo->schema()->columns());

                    if (in_array('name', $columns)) {
                        $element->setDisplayFrom($foreignKey['property'].'.name');
                    }
                }

                $this->add($element);
            }
        }

        $primaryKey = $table->primaryKey();
        if (!is_array($primaryKey)) {
            $this->get($primaryKey)->setControlType('hidden');
        }
    }

    public function get($name)
    {
        $element = null;
        if (array_key_exists($name, $this->order)) {
            if ($this->elements->offsetExists($this->order[$name])) {
                $element = $this->elements->offsetGet($this->order[$name]);
            }
        } else {
            pr($name . ' does not exists');die;
        }
        return $element;
    }

    public function addNew($name)
    {
        $element = PageElement::create($name);
        $this->add($element);
        return $element;
    }

    public function add(PageElement $element)
    {
        $this->elements->offsetSet($this->elements->count(), $element);
        $this->order[$element->getName()] = count($this->order);
    }

    public function addFilter($name)
    {
        $filter = new PageFilter($name);
        $this->filters->offsetSet($name, $filter);
        return $filter;
    }

    private function elementsToJSON()
    {
        $json = [];

        foreach ($this->elements as $element) {
            $name = $element->getName();
            if (!$this->isExcluded($name)) {
                $controlType = $element->getControlType();
                $isDropdownType = $controlType == 'dropdown';
                $noDropdownOptions = is_null($element->getOptions());
                if ($isDropdownType && $noDropdownOptions) {
                    $this->populateDropdownOptions($element);
                }
                $json[$name] = $element->getJSON();
            }
        }
        return $json;
    }

    private function filtersToJSON()
    {
        $json = [];
        foreach ($this->filters as $filter) {
            $json[$filter->getName()] = $filter->getJSON();
        }
        return $json;
    }

    private function tabsToArray()
    {
        $array = [];
        foreach ($this->tabs as $name => $tab) {
            $array[$name] = $tab->toArray();
        }
        return $array;
    }

    private function populateDropdownOptions(PageElement $element)
    {
        if ($this->hasMainTable()) {
            $table = $this->getMainTable();
            $foreignKey = $element->getForeignKey();
            if ($foreignKey) {
                $association = $foreignKey['name'];
                $element->setOptions($table->$association->find('list')->toArray());
            }
        }
    }

    public function exclude($fields)
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        foreach ($fields as $name) {
            if (array_key_exists($name, $this->order)) {
                $this->get($name)->setVisible(false);
                $this->excludedFields[] = $name;
            }
        }
    }

    public function isExcluded($name)
    {
        return in_array($name, $this->excludedFields);
    }

    public function move($source)
    {
        $this->moveSourceField = $source;
        return $this;
    }

    public function first()
    {
        $elements = $this->elements;
        $count = $elements->count();
        // move all items + 1 from first position
        for ($i=$count-1; $i>=0; $i--) {
            $element = $elements->offsetGet($i);
            $elements->offsetSet($i+1, $element);
            $this->order[$element->getName()] = $i+1;
        }

        // insert source item to destination position
        $sourceOrder = $this->order[$this->moveSourceField];
        $elements->offsetSet(0, $elements->offsetGet($sourceOrder));
        $this->order[$this->moveSourceField] = 0;

        // remove the extra source item in the array
        $elements->offsetUnset($sourceOrder);

        // reset the position number
        for ($i=$sourceOrder; $i<$count; $i++) {
            $element = $elements->offsetGet($i+1);
            $elements->offsetSet($i, $element);
            $elements->offsetUnset($i+1);
            $this->order[$element->getName()] = $i;
        }

        $this->moveSourceField = null;
        return $elements->offsetGet(0);
    }

    public function after($destination)
    {
        $elements = $this->elements;
        $destinationOrder = $this->order[$destination];
        $count = $elements->count();

        // move all items + 1 from destination position
        for ($i=$count; $i>$destinationOrder+1; $i--) {
            $element = $elements->offsetGet($i-1);
            $elements->offsetSet($i, $element);
            $this->order[$element->getName()] = $i;
        }

        // insert source item to destination position
        $sourceOrder = $this->order[$this->moveSourceField];
        $elements->offsetSet($destinationOrder+1, $elements->offsetGet($sourceOrder));

        // updates $this->order with new position of source
        $sourceField = $elements->offsetGet($this->order[$this->moveSourceField]);
        $this->order[$sourceField->getName()] = $destinationOrder+1;

        // remove the extra source item in the array
        $elements->offsetUnset($sourceOrder);

        // reset the position number
        for ($i=$sourceOrder; $i<$count; $i++) {
            $element = $elements->offsetGet($i+1);
            $elements->offsetSet($i, $element);
            $elements->offsetUnset($i+1);
            $this->order[$element->getName()] = $i;
        }

        // reset the source value
        $this->moveSourceField = null;
        return $sourceField;
    }

    public function isForeignKey(Table $table, $field)
    {
        foreach ($table->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // belongsTo associations
                if ($field === $assoc->foreignKey()) {
                    return ['name' => $assoc->name(), 'property' => $assoc->property()];
                }
            }
        }
        return false;
    }
}
