<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use CustomField\Model\Behavior\SetupBehavior;

class SetupCheckboxBehavior extends SetupBehavior
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function onSetCheckboxElements(Event $event, Entity $entity)
    {
        $fieldType = strtolower($this->fieldTypeCode);
        $this->_table->field('options', [
            'type' => 'element',
            'order' => 0,
            'element' => 'CustomField.Setup/' . $fieldType,
            'visible' => true,
            'valueClass' => 'table-full-width'
        ]);
        $this->_table->field('id', [
            'type' => 'hidden'
        ]);
        $this->sortFieldOrder('id','options');
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $queryCopy = clone($query);
        $entity = $queryCopy->first();
        if ($entity->field_type == $this->fieldTypeCode) {
            $query->contain(['CustomFieldOptions']);
        }
    }

    public function addEditOnChangeType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        $request = $model->request;
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($model->getAlias(), $request->getData())) {
                if (array_key_exists('custom_field_options', $request->getData()[$model->getAlias()])) {
                    unset($data[$model->getAlias()]['custom_field_options']);
                }
            }
        }
    }

    public function addEditOnAddOption(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;

        if ($data[$model->getAlias()]['field_type'] == $this->fieldTypeCode) {
            $fieldOptions = [
                'name' => '',
                'visible' => 1
            ];
            $data[$model->getAlias()]['custom_field_options'][] = $fieldOptions;
            $data[$model->getAlias()]['id'] = $entity->id;

            //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
            $options['associated'] = [
                'CustomFieldOptions' => ['validate' => false]
            ];
        }
    }
}
