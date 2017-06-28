<?php
namespace Page\Model\Entity;

use ArrayObject;

use Cake\Utility\Inflector;
use Cake\Log\Log;

class PageElement
{
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
    protected $options; // options for dropdown control type
    protected $value; // current selected value
    protected $wildcard;
    protected $extra; // extra information

    public function __construct($fieldName, array $attributes)
    {
        $this->name = $fieldName;
        $this->value = '';
        $this->visible = true;
        $this->readonly = false;
        $this->disabled = false;
        $this->wildcard = 'full';
        $this->extra = [];

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

    public function getType()
    {
        return $this->type;
    }

    public function attr($name, $value = null)
    {
        if (is_array($name) && is_null($value)) {
            foreach ($name as $key => $val) {
                $this->extra[$key] = $val;
            }
        } elseif (!is_null($value)) {
            $this->extra[$name] = $value;
        }
        return $this;
    }

    public function getExtra()
    {
        return $this->extra;
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

    public function getForeignKey()
    {
        return $this->foreignKey;
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

    public function getAliasField()
    {
        if (!empty($this->model) && empty($this->aliasField)) {
            $this->aliasField = $this->model . '.' . $this->name;
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
            'name' => 'get',
            'model' => 'get',
            'aliasField' => 'get',
            'controlType' => 'get',
            'readonly' => 'is',
            'disabled' => 'is',
            'displayFrom' => 'get',
            'label' => 'get',
            'sortable' => 'is',
            'maxlength' => 'get',
            'required' => 'is',
            'visible' => 'is',
            'foreignKey' => 'get',
            'defaultValue' => 'get',
            'options' => 'get',
            'value' => 'get'
        ];

        $name = $this->name;

        // if ($this->excludePrimaryKey) {
        //     $this->exclude($this->table->primaryKey());
        // }

        foreach ($visibleProperties as $property => $method) {
            $propertyMethod = $method . ucfirst($property);
            if ($property != 'displayFrom') {
                $array[$property] = $this->$propertyMethod();
            } elseif ($property == 'displayFrom' && $this->$propertyMethod()) {
                $array[$property] = $this->$propertyMethod();
            }
        }

        $extra = $this->getExtra();
        foreach ($extra as $attr => $value) {
            $array[$attr] = $value;
        }

        return $array;
    }
}
