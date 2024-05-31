<?php
namespace Restful\Model\Entity;

use ArrayObject;

use Cake\ORM\Table;
use Cake\Log\Log;

class Schema extends ArrayObject
{
    private $table;
    private $order = [];
    private $moveSourceField;
    private $excludedFields = [];
    private $excludePrimaryKey = false;
    private $filters = [];

    public function __construct(Table $table)
    {
        parent::__construct([]);
        $this->table = $table;
    }

    public function clear()
    {
        $this->exchangeArray([]);
        $this->order = [];
        $this->excludedFields = [];
    }

    public function get($fieldName)
    {
        $field = null;
        if (array_key_exists($fieldName, $this->order)) {
            if ($this->offsetExists($this->order[$fieldName])) {
                $field = $this->offsetGet($this->order[$fieldName]);
            }
        }
        return $field;
    }

    public function addNew($name)
    {
        $field = Field::create($name);
        $this->add($field);
        return $field;
    }

    public function add(Field $field)
    {
        // hide the primary key field
        $primaryKey = $this->table->primaryKey();
        if (!is_array($primaryKey) && $primaryKey == $field->name()) {
            $field->controlType('hidden');
        }

        // setup displayFrom
        if ($field->foreignKey()) {
            $foreignKey = $field->foreignKey();
            $belongsTo = $this->table->{$foreignKey['name']};
            $entity = $belongsTo->newEntity();
            $columns = array_merge($entity->visibleProperties(), $belongsTo->schema()->columns());

            if (in_array('name', $columns)) {
                $field->displayFrom($foreignKey['property'].'.name');
            }
        }

        $this[$this->count()] = $field;
        $this->order[$field->name()] = count($this->order);
    }

    public function toArray()
    {
        // properties to be exposed to client browser
        $visibleProperties = ['controlType', 'readonly', 'disabled', 'displayFrom', 'label', 'length', 'required', 'visible', 'foreignKey', 'defaultValue', 'options', 'value'];
        $array = [];

        if ($this->excludePrimaryKey) {
            $this->exclude($this->table->primaryKey());
        }

        foreach ($this as $field) {
            $name = $field->name();
            if (in_array($name, $this->excludedFields)) continue;

            $array[$name] = ['key' => $name];

            foreach ($visibleProperties as $property) {
                if (method_exists($field, $property)) {
                    if ($property != 'displayFrom') {
                        $array[$name][$property] = $field->$property();
                    } elseif ($property == 'displayFrom' && $field->$property()) {
                        $array[$name][$property] = $field->$property();
                    }
                }
            }

            $extra = $field->extra();
            foreach ($extra as $attr => $value) {
                $array[$name][$attr] = $value;
            }
        }
        return $array;
    }

    public function isForeignKey($field)
    {
        $table = $this->table;
        foreach ($table->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // belongsTo associations
                if ($field === $assoc->foreignKey()) {
                    return ['name' => $assoc->name(), 'property' => $assoc->property()];
                }
            }
        }
        return false;
    }

    public function hide(array $fields)
    {
        foreach ($fields as $name) {
            $this->get($name)->visible(false);
        }
    }

    public function exclude($fields)
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        foreach ($fields as $name) {
            if (array_key_exists($name, $this->order)) {
                $this->get($name)->visible(false);
                $this->excludedFields[] = $name;
            }
        }
    }

    public function excludePrimaryKey($bool = true)
    {
        $this->excludePrimaryKey = $bool;
    }

    public function configureFilters($filters)
    {
        $this->filters = $filters;
    }

    public function hasFilters()
    {
        return !empty($this->filters);
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function move($source)
    {
        $this->moveSourceField = $source;
        return $this;
    }

    public function first()
    {
        $count = $this->count();
        // move all items + 1 from first position
        for ($i=$count-1; $i>=0; $i--) {
            $field = $this->offsetGet($i);
            $this->offsetSet($i+1, $field);
            $this->order[$field->name()] = $i+1;
        }

        // insert source item to destination position
        $sourceOrder = $this->order[$this->moveSourceField];
        $this->offsetSet(0, $this->offsetGet($sourceOrder));
        $this->order[$this->moveSourceField] = 0;

        // remove the extra source item in the array
        $this->offsetUnset($sourceOrder);

        // reset the position number
        for ($i=$sourceOrder; $i<$count; $i++) {
            $field = $this->offsetGet($i+1);
            $this->offsetSet($i, $field);
            $this->offsetUnset($i+1);
            $this->order[$field->name()] = $i;
        }

        $this->moveSourceField = null;
        return $this->offsetGet(0);
    }

    public function after($destination)
    {
        $destinationOrder = $this->order[$destination];
        $count = $this->count();

        // move all items + 1 from destination position
        for ($i=$count; $i>$destinationOrder+1; $i--) {
            $field = $this->offsetGet($i-1);
            $this->offsetSet($i, $field);
            $this->order[$field->name()] = $i;
        }

        // insert source item to destination position
        $sourceOrder = $this->order[$this->moveSourceField];
        $this->offsetSet($destinationOrder+1, $this->offsetGet($sourceOrder));

        // updates $this->order with new position of source
        $sourceField = $this->offsetGet($this->order[$this->moveSourceField]);
        $this->order[$sourceField->name()] = $destinationOrder+1;

        // remove the extra source item in the array
        $this->offsetUnset($sourceOrder);

        // reset the position number
        for ($i=$sourceOrder; $i<$count; $i++) {
            $field = $this->offsetGet($i+1);
            $this->offsetSet($i, $field);
            $this->offsetUnset($i+1);
            $this->order[$field->name()] = $i;
        }

        // reset the source value
        $this->moveSourceField = null;
        return $sourceField;
    }
}
