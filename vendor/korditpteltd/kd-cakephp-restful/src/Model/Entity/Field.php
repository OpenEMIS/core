<?php
namespace Restful\Model\Entity;

use ArrayObject;

use Cake\Utility\Inflector;
use Cake\Log\Log;

class Field
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

    protected $belongsTo;
    protected $foreignKey;
    protected $required;
    protected $controlType; // control type
    protected $readonly;
    protected $disabled;
    protected $displayFrom;
    protected $label;
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
                Log::write('debug', 'Restful Field Object: New attribute (' . $name . ') found in field attributes');
            }
        }

        if (array_key_exists('null', $attributes)) {
            $this->required = $attributes['null'] == false;
        }

        if (array_key_exists('default', $attributes)) {
            $this->default = $attributes['default'];
        }

        $label = Inflector::humanize($fieldName);
        if ($this->foreignKey()) {
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
        $field = new Field($name, $attributes);
        return $field;
    }

    public function name()
    {
        return $this->name;
    }

    public function type()
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

    public function extra()
    {
        return $this->extra;
    }

    public function required($required = null)
    {
        if (is_null($required)) {
            return $this->required;
        } else {
            $this->required = $required;
            return $this;
        }
    }

    public function controlType($controlType = null)
    {
        if (is_null($controlType)) {
            return $this->controlType;
        } else {
            $this->controlType = $controlType;
            return $this;
        }
    }

    public function readonly($readonly = null)
    {
        if (is_null($readonly)) {
            return $this->readonly;
        } else {
            $this->readonly = $readonly;
            return $this;
        }
    }

    public function disabled($disabled = null)
    {
        if (is_null($disabled)) {
            return $this->disabled;
        } else {
            $this->disabled = $disabled;
            return $this;
        }
    }

    public function label($label = null)
    {
        if (is_null($label)) {
            return __($this->label);
        } else {
            $this->label = $label;
            return $this;
        }
    }

    public function length()
    {
        return $this->length;
    }

    public function defaultValue()
    {
        return $this->default;
    }

    public function visible($visible = null)
    {
        if (is_null($visible)) {
            return $this->visible;
        } else {
            $this->visible = $visible;
            return $this;
        }
    }

    public function foreignKey()
    {
        return $this->foreignKey;
    }

    public function belongsTo()
    {
        return $this->belongsTo;
    }

    public function displayFrom($displayFrom = null)
    {
        if (is_null($displayFrom)) {
            return $this->displayFrom;
        } else {
            $this->displayFrom = $displayFrom;
            return $this;
        }
    }

    public function options($options = null)
    {
        if (is_null($options)) {
            return $this->options;
        } else {
            $this->options = $options;
            return $this;
        }
    }

    public function value($value = null)
    {
        if (is_null($value)) {
            return $this->value;
        } else {
            $this->value = $value;
            return $this;
        }
    }

    public function onChange()
    {
        //
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
}
