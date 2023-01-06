<?php
namespace Page\Model\Entity;

use ArrayObject;

use Cake\Utility\Inflector;
use Cake\Log\Log;

class PageFilter
{
    private $name;
    private $options = [];
    private $value;
    private $dependentOn;
    private $params;
    private $model;
    private $finder;
    private $resetAll;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public static function create($name)
    {
        $attributes = [
            'type' => 'string',
            'length' => '0',
            'unsigned' => false,
            'null' => true,
            'comment' => '',
            'autoIncrement' => false,
            'precision' => false,
            'foreignKey' => false
        ];
        $field = new PageElement($name, $attributes);
        return $field;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    public function getDependentOn()
    {
        return $this->dependentOn;
    }

    public function setDependentOn($dependentOn)
    {
        $this->dependentOn = (array) $dependentOn;
        return $this;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setParams($params)
    {
        $this->params = $params;
        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function setModel($model)
    {
        $this->model = $model;
        return $this;
    }

    public function getFinder()
    {
        return $this->finder;
    }

    public function setFinder($finder)
    {
        $this->finder = $finder;
        return $this;
    }

    public function setResetAll(bool $reset)
    {
        $this->resetAll = $reset;
        return $this;
    }

    public function getResetAll()
    {
        return $this->resetAll;
    }

    public function getJSON()
    {
        // properties to be exposed to client browser
        $visibleProperties = [
            'options' => 'get',
            'value' => 'get',
            'dependentOn' => 'get',
            'resetAll' => 'get',
            'params' => 'get',
            // 'model' => 'get',
            // 'finder' => 'get'
        ];

        $array = [];

        foreach ($visibleProperties as $property => $method) {
            $propertyMethod = $method . ucfirst($property);
            $array[$property] = $this->$propertyMethod();
        }

        return $array;
    }
}
