<?php
namespace CustomField\Model\Behavior;

use Cake\Utility\Inflector;
use Cake\ORM\Behavior;

class SetupBehavior extends Behavior
{
    protected $fieldTypeCode;
    protected $fieldType;
    protected $inputLimits;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $class = basename(str_replace('\\', '/', get_class($this)));
        $class = str_replace('Setup', '', $class);
        $class = str_replace('Behavior', '', $class);

        $code = strtoupper(Inflector::underscore($class));
        $this->_table->setFieldTypes($code);
        $this->fieldTypeCode = $code;
        $this->fieldType = $class;
        $this->inputLimits = [
            'text_value' => ['max' => 250],
            'number_value' => ['min' => -2147483648, 'max' => 2147483647],
            'decimal_value' => [
                'length' => [
                    'min' => 1,
                    'max' => 10
                ],
                'precision' => [
                    'min' => 0,
                    'max' => 5
                ]
            ]
        ];
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $eventMap = [
            'Setup.set'.$this->fieldType.'Elements' => 'onSet'.$this->fieldType.'Elements',
            'ControllerAction.Model.viewEdit.beforeQuery' => 'viewEditBeforeQuery',
            'ControllerAction.Model.addEdit.onChangeType' => 'addEditOnChangeType',
            'ControllerAction.Model.addEdit.onAddOption' => 'addEditOnAddOption',
            'ControllerAction.Model.addEdit.onAddColumn' => 'addEditOnAddColumn',
            'ControllerAction.Model.addEdit.onAddRow' => 'addEditOnAddRow',
            'ControllerAction.Model.add.beforeAction' => 'addBeforeAction',
            'ControllerAction.Model.edit.afterQuery' => 'editAfterQuery',
        ];

        foreach ($eventMap as $event => $method) {
            if (!method_exists($this, $method)) {
                continue;
            }
            $events[$event] = $method;
        }
        return $events;
    }

    protected function sortFieldOrder($field = null)
    {
        $fields = $this->_table->fields;

        $order = 0;
        $fieldOrder = [];
        $ignoreFields = ['id', 'modified_user_id', 'modified', 'created_user_id', 'created'];
        if (!is_null($field)) {
            array_unshift($ignoreFields, $field);
        }

        foreach ($fields as $fieldName => $fieldAttr) {
            if (!in_array($fieldName, $ignoreFields)) {
                $order = $fieldAttr['order'] > $order ? $fieldAttr['order'] : $order;
                $fieldOrder[$fieldAttr['order']] = $fieldName;
            }
        }

        foreach ($ignoreFields as $key => $field) {
            $fieldOrder[++$order] = $field;
        }

        ksort($fieldOrder);
        $this->_table->setFieldOrder($fieldOrder);
    }
}
