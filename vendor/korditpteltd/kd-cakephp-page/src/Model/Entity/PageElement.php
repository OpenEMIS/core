<?php
namespace Page\Model\Entity;

use ArrayObject;

use Cake\Utility\Inflector;
use Cake\Log\Log;

class PageElement
{
    protected $id;
    protected $name;
    protected $type; // data type
    protected $length;
    protected $unsigned;
    protected $null;
    protected $default;
    protected $comment;
    protected $autoIncrement;
    protected $precision;
    protected $fixed;
    protected $collate;

    protected $key;
    protected $model;
    protected $aliasField;
    protected $belongsTo;
    protected $foreignKey;
    protected $required;
    protected $controlType; // control type
    protected $readonly;
    protected $maxlength;
    protected $disabled;
    protected $displayFrom;
    protected $label;
    protected $sortable;
    protected $visible;
    protected $dependentOn;
    protected $params;
    protected $options; // options for dropdown control type
    protected $value; // current selected value
    protected $wildcard;
    protected $attributes; // html attributes
    protected $extra; // any other attributes

    public function __construct($fieldName, array $attributes)
    {
        $this->key = $fieldName;
        $this->name = $fieldName;
        $this->value = '';
        $this->visible = true;
        $this->readonly = false;
        $this->disabled = false;
        $this->wildcard = 'full';
        $this->sortable = false;
        $this->options = [];
        $this->attributes = [];
        $this->extra = new ArrayObject();

        foreach ($attributes as $name => $value) {
            if (property_exists($this, $name)) {
                $this->{$name} = $value;
            } else {
                Log::write('debug', 'Element Object: New attribute (' . $name . ') found in field attributes');
            }
        }

        if (array_key_exists('null', $attributes)) {
            $this->setRequired($attributes['null'] == false);
        }

        $label = Inflector::humanize($fieldName);
        if ($this->getForeignKey()) {
            $label = substr($label, 0, strlen($label)-3); // to remove 'Id' from the label
        }
        $this->label = $label;

        if ($fieldName == 'password') {
            $this->controlType = 'password';
        } elseif ($this->type == 'text') {
            $this->controlType = 'textarea';
        } elseif ($this->type == 'uuid') {
            $this->controlType = 'hidden';
        } else {
            $this->controlType = $this->type;
        }

        if (in_array($this->type, ['string', 'integer', 'text'])) {
            $this->setSortable(true);
        }

        if (array_key_exists('model', $attributes)) {
            $this->name = $attributes['model'] . '.' . $this->name;
        }
    }

    public static function create($name, $attributes)
    {
        $_attributes = [
            'type' => 'string',
            'length' => '0',
            'unsigned' => false,
            'null' => true,
            'comment' => '',
            'autoIncrement' => false,
            'precision' => false,
            'foreignKey' => false
        ];

        $attributes = array_merge($_attributes, $attributes);
        $field = new PageElement($name, $attributes);
        return $field;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function set($name, $value)
    {
        $this->extra->offsetSet($name, $value);
        return $this;
    }

    public function get($name)
    {
        if ($this->extra->offsetExists($name)) {
            return $this->extra->offsetGet($name);
        }
        return null;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function setName()
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setAttributes($name, $value = null)
    {
        if (is_array($name) && is_null($value)) {
            foreach ($name as $key => $val) {
                $this->attributes[$key] = $val;
            }
        } elseif (!is_null($value)) {
            $this->attributes[$name] = $value;
        }

        if ($this->controlType == 'dropdown' && $name == 'multiple' && $value == true) {
            $this->name .= '._ids';
        }
        return $this;
    }

    // public function setAttributes($key, $value)
    // {
    //     $this->attributes[$key] = $value;
    //     return $this;
    // }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function isRequired()
    {
        return $this->required;
    }

    public function setRequired($required)
    {
        $this->required = $required;
        return $this;
    }

    public function getControlType()
    {
        return $this->controlType;
    }

    public function setControlType($controlType)
    {
        $this->controlType = $controlType;
        return $this;
    }

    public function isReadonly()
    {
        return $this->readonly;
    }

    public function setReadonly($readonly)
    {
        $this->readonly = $readonly;
        return $this;
    }

    public function getMaxlength()
    {
        return $this->getLength();
    }

    public function setMaxlength($maxlength)
    {
        return $this->setLength($maxlength);
    }

    public function isDisabled()
    {
        return $this->disabled;
    }

    public function setDisabled($disabled)
    {
        // if input is disabled, it should not be mandatory
        $this->required = false;
        $this->disabled = $disabled;
        return $this;
    }

    public function getLabel()
    {
        return __($this->label);
    }

    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    public function isSortable()
    {
        return $this->sortable;
    }

    public function setSortable($sortable)
    {
        $this->sortable = $sortable;
        return $this;
    }

    public function setLength($length)
    {
        $this->length = $length;
        return $this;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function getDefaultValue()
    {
        return $this->default;
    }

    public function isVisible()
    {
        return $this->visible;
    }

    public function setVisible($visible)
    {
        $this->visible = $visible;
        return $this;
    }

    public function setDependentOn($dependentOn)
    {
        $this->dependentOn = $dependentOn;
        return $this;
    }

    public function getDependentOn()
    {
        return $this->dependentOn;
    }

    public function setParams($params)
    {
        $this->params = $params;
        return $this;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    public function getModel()
    {
        return $this->model;
    }

    // public function setModel($model)
    // {
    //     $this->model = $model;
    //     return $this;
    // }

    public function getAliasField()
    {
        if (empty($this->aliasField)) {
            if (!empty($this->model)) {
                $this->aliasField = $this->model . '.' . $this->name;
            } else {
                $this->aliasField = $this->name;
            }
        }
        return $this->aliasField;
    }

    public function setAliasField($aliasField)
    {
        $this->aliasField = $aliasField;
        return $this;
    }

    public function getBelongsTo()
    {
        return $this->belongsTo;
    }

    public function getDisplayFrom()
    {
        return $this->displayFrom;
    }

    public function setDisplayFrom($displayFrom)
    {
        $this->displayFrom = $displayFrom;
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions($options, $empty = true)
    {
        if (empty($options) && $empty !== false) {
            $this->options = ['' => __('No Options')];
        } else {
            $firstOption = current($options);
            if (is_array($firstOption) && array_key_exists('value', $firstOption) && array_key_exists('text', $firstOption)) {
                if ($empty === true && !strlen($firstOption['value']) == 0) {
                    $this->options = array_merge([['value' => '', 'text' => '-- ' . __('Select') . ' --']], $options);
                } elseif (is_string($empty)) {
                    $this->options = array_merge([['value' => '', 'text' => __($empty)]], $options);
                } else {
                    $this->options = $options;
                }
            } else { // TODO: need to convert to [value, text] format
                if ($empty === true) {
                    $this->options = ['' => '-- ' . __('Select') . ' --'] + $options;
                } elseif (is_string($empty)) {
                    $this->options = ['' => __($empty)] + $options;
                } else {
                    $this->options = $options;
                }
            }
        }

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

    public function wildcard($wildcard = null /* 'full' || 'left' || 'right' */)
    {
        $allowed = ['full', 'left', 'right'];
        if (is_null($wildcard)) {
            return $this->wildcard;
        } elseif (in_array($wildcard, $allowed)) {
            $this->wildcard = $wildcard;
            return $this;
        }
    }

    public function getJSON()
    {
        // properties to be exposed to client browser
        $visibleProperties = [
            'key' => 'get',
            'controlType' => 'get',
            'displayFrom' => 'get',
            'label' => 'get',
            'sortable' => 'is',
            'visible' => 'is',
            'dependentOn' => 'get',
            'params' => 'get',
            'foreignKey' => 'get',
            'attributes' => 'get'
        ];

        $htmlAttributes = [
            'id' => 'get',
            'name' => 'get',
            'readonly' => 'is',
            'disabled' => 'is',
            'maxlength' => 'get',
            'required' => 'is',
            'value' => 'get'
        ];

        $properties = [];

        foreach ($visibleProperties as $property => $method) {
            $propertyMethod = $method . ucfirst($property);
            $propertyValue = $this->$propertyMethod();

            if ($property == 'displayFrom' && $propertyValue) {
                $properties[$property] = $propertyValue;
            } else if (!is_null($propertyValue)) {
                if ($property == 'dependentOn') {
                    $properties[$property] = $this->getKey() . '/' . $propertyValue;
                } else {
                    $properties[$property] = $propertyValue;
                }
            }
        }

        foreach ($htmlAttributes as $property => $method) {
            $propertyMethod = $method . ucfirst($property);
            $propertyValue = $this->$propertyMethod();
            if (!is_null($propertyValue)) {
                $properties['attributes'][$property] = $propertyValue;
            }
        }

        if ($this->getControlType() == 'dropdown') {
            $properties['options'] = $this->getOptions();
        }

        if ($this->extra->count() > 0) {
            $properties = array_merge($properties, $this->extra->getArrayCopy());
        }

        return $properties;
    }
}
