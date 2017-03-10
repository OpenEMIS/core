<?php
namespace Restful\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;

class SavingBehavior extends Behavior {

    protected $_defaultConfig = [
        'fields' => [
            'excludes' => ['created_user_id', 'created']
        ],
        'triggerOn' => 'create'
    ];

    private $excludedFields =  [];

    public function initialize(array $config)
    {
        $this->excludedFields = $this->config('fields.excludes');
    }

    public function addExcludedFields($field)
    {
        if (is_array($field)) {
            $this->excludedFields = array_merge($this->excludedFields, $field);
        } else {
            $this->excludedFields[] = $field;
        }
    }

    public function getExcludedFields()
    {
        return $this->excludedFields;
    }

    public function removeExcludedFields($field)
    {
        if (!is_array($field)) {
            $field = [$field];
        }
        $this->excludedFields = array_diff($this->excludedFields, $field);
    }

    public function buildValidator(Event $event, Validator $validator, $name)
    {
        if ($name == 'default') {
            $model = $this->_table;
            $triggerOn = $this->config('triggerOn');

            // Check on database not null fields
            $schema = $model->schema();
            $columns = $schema->columns();
            foreach ($columns as $col) {
                $attr = $schema->column($col);

                if ($validator->hasField($col)) {
                    $set = $validator->field($col);

                    if (!$set->isEmptyAllowed()) {
                        $set->add('notBlank', ['rule' => 'notBlank']);
                    }
                    if (!$set->isPresenceRequired()) {
                        if ($this->isForeignKey($col)) {
                            $validator->requirePresence($col, $triggerOn);
                        }
                    }
                } else {
                    if (array_key_exists('null', $attr)) {
                        $ignoreFields = $this->getExcludedFields();
                        if ($attr['null'] === false // not nullable
                            && (array_key_exists('default', $attr) && strlen($attr['default']) == 0) // don't have a default value in database
                            && $col !== 'id' // not a primary key
                            && !in_array($col, $ignoreFields) // fields not excluded
                        ) {
                            $validator->add($col, 'notBlank', ['rule' => 'notBlank']);
                            if ($this->isForeignKey($col)) {
                                $validator->requirePresence($col, $triggerOn);
                            }
                        }
                    }
                }
            }
        }
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        if ($options->offsetExists('extra')) {
            $action = $options['extra']->offsetExists('action') ? $options['extra']['action'] : null;

            $model->dispatchEvent('Restful.CRUD.allActions.beforePatch', [$data, $options], $model);

            if ($action == 'add' || $action == 'edit') {
                $model->dispatchEvent('Restful.CRUD.addEdit.beforePatch', [$data, $options], $model);
            }

            if ($options['extra']->offsetExists('action')) {
                $model->dispatchEvent('Restful.CRUD.'.lcfirst(Inflector::camelize($options['extra']['action'])).'.beforePatch', [$data, $options], $model);
            }
        }
    }

    private function isForeignKey($field)
    {
        $model = $this->_table;
        foreach ($model->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // belongsTo associations
                if ($field === $assoc->foreignKey()) {
                    return true;
                }
            }
        }
        return false;
    }
}
