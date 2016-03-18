<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;

class RenderTableBehavior extends RenderBehavior {
	public function initialize(array $config) {
        parent::initialize($config);
    }

	public function onGetCustomTableElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        $fieldType = strtolower($this->fieldTypeCode);
        $customField = $attr['customField'];
        $fieldId = $attr['customField']->id;
        $cellValues = $attr['customTableCells'];
        $form = $event->subject()->Form;

        $tableHeaders = [];
        $tableCells = [];
        $cellCount = 0;

        foreach ($customField->custom_table_rows as $rowKey => $rowObj) {
            $rowData = [];
            $rowData[] = $rowObj->name;

            $colCount = 0;
            foreach ($customField->custom_table_columns as $colKey => $colObj) {
                if (!array_key_exists($colObj->id, $tableHeaders)) {
                    $tableHeaders[$colObj->id] = $colObj->name;
                }
                if ($colCount++ == 0) {continue;}

                $tableColumnId = $colObj->id;
                $tableRowId = $rowObj->id;

                $fieldPrefix = $attr['model'] . '.custom_table_cells.' . $fieldId;
                $cellPrefix = "$fieldPrefix.$tableRowId.$tableColumnId";

                $cellValue = "";
                $cellInput = "";
                $cellOptions = ['label' => false];

                if (isset($cellValues[$fieldId][$tableRowId][$tableColumnId])) {
                    $cellValue = $cellValues[$fieldId][$tableRowId][$tableColumnId];
                    $cellOptions['value'] = $cellValue;
                }

                $cellInput .= $form->input($cellPrefix.".text_value", $cellOptions);

                if ($action == 'view') {
                    $rowData[$colKey] = $cellValue;
                } else if ($action == 'edit') {
                    $rowData[$colKey] = $cellInput;
                }
            }

            $tableCells[$rowKey] = $rowData;
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        if ($action == 'view') {
            $value = $event->subject()->renderElement('CustomField.Render/'.$fieldType, ['attr' => $attr]);
        } else if ($action == 'edit') {
            $value = $event->subject()->renderElement('CustomField.Render/'.$fieldType, ['attr' => $attr]);
        }

        $event->stopPropagation();
        return $value;
    }

    public function processTableValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings) {
        $settings['valueKey'] = 'text_value';
        $tableCells = $settings['tableCells'];

        $alias = $this->_table->alias();
        $values = $data[$alias]['custom_table_cells'];
        $fieldKey = $settings['fieldKey'];
        $tableColumnKey = $settings['tableColumnKey'];
        $tableRowKey = $settings['tableRowKey'];
        $valueKey = $settings['valueKey'];

        foreach ($values as $fieldId => $rows) {
            $settings['deleteFieldIds'][] = $fieldId;
            foreach ($rows as $rowId => $columns) {
                foreach ($columns as $columnId => $obj) {
                    $cellValue = $obj[$valueKey];
                    if (strlen($cellValue) > 0) {
                        $tableCells[] = [
                            $fieldKey => $fieldId,
                            $tableRowKey => $rowId,
                            $tableColumnKey => $columnId,
                            $valueKey => $cellValue
                        ];
                    }
                }
            }
        }

        $settings['tableCells'] = $tableCells;
    }
}
