<?php
namespace Restful\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Validation\Validator;

class SavingBehavior extends Behavior {

    protected $_defaultConfig = [
        'fields' => [
            'excludes' => ['modified', 'created']
        ]
    ];

    public function buildValidator(Event $event, Validator $validator, $name)
    {
        if ($name == 'default') {
            $schema = $this->_table->schema();

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
                            $validator->requirePresence($col, 'create');
                        }
                    }
                } else {
                    if (array_key_exists('null', $attr)) {
                        $ignoreFields = $this->config('fields.excludes');
                        if ($attr['null'] === false // not nullable
                            && (array_key_exists('default', $attr) && strlen($attr['default']) == 0) // don't have a default value in database
                            && $col !== 'id' // not a primary key
                            && !in_array($col, $ignoreFields) // fields not excluded
                        ) {
                            $validator->add($col, 'notBlank', ['rule' => 'notBlank']);
                            if ($this->isForeignKey($col)) {
                                $validator->requirePresence($col, 'create');
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

        $model->dispatchEvent('Restful.CRUD.addEdit.beforePatch', [$data, $options], $model);

        if ($options->offsetExists('action')) {
            $model->dispatchEvent('Restful.CRUD.'.$options['action'].'.beforePatch', [$data, $options], $model);
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
