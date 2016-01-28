<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use CustomField\Model\Behavior\SetupBehavior;

class SetupTableBehavior extends SetupBehavior {
	public function initialize(array $config) {
        parent::initialize($config);
    }

    public function onSetTableElements(Event $event, Entity $entity) {
    	$fieldType = strtolower($this->fieldTypeCode);
		$this->_table->ControllerAction->addField('tables', [
            'type' => 'element',
            'order' => 0,
            'element' => 'CustomField.Setup/' . $fieldType,
            'visible' => true,
            'valueClass' => 'table-full-width'
        ]);
        $this->sortFieldOrder('tables');
    }

    public function viewEditBeforeQuery(Event $event, Query $query) {
        $queryCopy = clone($query);
        $entity = $queryCopy->first();
        if ($entity->field_type == $this->fieldTypeCode) {
            $query->contain(['CustomTableColumns', 'CustomTableRows']);
        }
    }

    public function addEditOnChangeType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
        $model = $this->_table;
        $request = $model->request;
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($model->alias(), $request->data)) {
                if (array_key_exists('custom_table_columns', $request->data[$model->alias()])) {
                    unset($data[$model->alias()]['custom_table_columns']);
                }
                if (array_key_exists('custom_table_rows', $request->data[$model->alias()])) {
                    unset($data[$model->alias()]['custom_table_rows']);
                }
            }
        }
    }

    public function addEditOnAddColumn(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
        $model = $this->_table;
        if ($data[$model->alias()]['field_type'] == $this->fieldTypeCode) {
            $columnOptions = [
                'name' => '',
                'visible' => 1
            ];
            $data[$this->_table->alias()]['custom_table_columns'][] = $columnOptions;

            //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
            $options['associated'] = [
                'CustomTableColumns' => ['validate' => false],
                'CustomTableRows' => ['validate' => false]
            ];
        }
    }

    public function addEditOnAddRow(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
        $model = $this->_table;
        if ($data[$model->alias()]['field_type'] == $this->fieldTypeCode) {
            $rowOptions = [
                'name' => '',
                'visible' => 1
            ];
            $data[$this->_table->alias()]['custom_table_rows'][] = $rowOptions;

            //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
            $options['associated'] = [
                'CustomTableColumns' => ['validate' => false],
                'CustomTableRows' => ['validate' => false]
            ];
        }
    }
}
