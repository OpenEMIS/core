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
        $fieldKey = $attr['attr']['fieldKey'];
        $formKey = $attr['attr']['formKey'];
        $tableColumnKey = $attr['attr']['tableColumnKey'];
        $tableRowKey = $attr['attr']['tableRowKey'];
        $form = $event->subject()->Form;

        $tableHeaders = [];
        $tableCells = [];
        $cellCount = 0;
        $fieldPrefix = $attr['model'] . '.custom_table_cells.' . $fieldId;
        $unlockFields = [];

        // validation rules
        $valueColumn = 'text_value';
        $cellAttr = [
            'type' => 'string'
        ];
        if ($customField->has('params') && !empty($customField->params)) {
            $params = json_decode($customField->params, true);

            if (array_key_exists('number', $params)) {
                $valueColumn = 'number_value';
                $cellAttr['type'] = 'number';
            } else if (array_key_exists('decimal', $params)) {
                $valueColumn = 'decimal_value';
                $cellAttr['type'] = 'number';

                $cellAttr['min'] = 0;
                $step = $this->getStepFromParams($params['decimal']);
                if (!is_null($step)) {
                    $cellAttr['step'] = $step;
                }
            }
        }
        // end

        $cellErrors = $entity->errors('custom_table_cells');
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

                
                $cellPrefix = "$fieldPrefix.$tableRowId.$tableColumnId";

                $cellValue = "";
                $cellInput = "";
                $cellOptions = ['label' => false];
                $cellOptions = array_merge($cellOptions, $cellAttr);

                if (isset($cellValues[$fieldId][$tableRowId][$tableColumnId])) {
                    $cellValue = $cellValues[$fieldId][$tableRowId][$tableColumnId][$valueColumn];
                    $cellOptions['value'] = $cellValue;
                }

                // start build error messages for each cell
                $errorInput = '';
                if (!empty($cellErrors)) {
                    foreach ($cellErrors as $errorKey => $errorObj) {
                        $cellEntity = $entity->custom_table_cells[$errorKey];

                        $cellFieldId = $cellEntity->{$fieldKey};
                        $cellRowId = $cellEntity->{$tableRowKey};
                        $cellColumnId = $cellEntity->{$tableColumnKey};

                        if ($cellFieldId == $fieldId && $cellRowId == $tableRowId && $cellColumnId == $tableColumnId) {
                            $errors = '';
                            foreach ($errorObj as $fieldCol => $fieldErrors) {
                                foreach ($fieldErrors as $fieldErrorRule => $fieldErrorMessage) {
                                    if (empty($errors)) {
                                        $errors .= $fieldErrorMessage;
                                    } else {
                                        $errors .= '<br>' . $fieldErrorMessage;
                                    }
                                }
                            }
                            $errorInput = '<div class="error-message">'.$errors.'</div>';
                            unset($cellErrors[$errorKey]);
                        }
                    }
                }
                // end build error messages for each cell
                $cellInput .= $form->input("$cellPrefix.$valueColumn", $cellOptions);
                if (!empty($errorInput)) {
                    $cellInput .= $errorInput;
                }

                $unlockFields[] = "$cellPrefix.$valueColumn";

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
            $value = $this->processRelevancyDisabled($entity, $value, $fieldId, $form, $unlockFields);
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
                    $textValue = NULL;
                    $numberValue = NULL;
                    $decimalValue = NULL;
                    if (array_key_exists('text_value', $obj)) {
                        $textValue = $obj['text_value'];
                    }
                    if (array_key_exists('number_value', $obj)) {
                        $numberValue = $obj['number_value'];
                    }
                    if (array_key_exists('decimal_value', $obj)) {
                        $decimalValue = $obj['decimal_value'];
                    }

                    if (strlen($textValue) > 0 || strlen($numberValue) > 0 || strlen($decimalValue) > 0) {
                        $tableCells[] = [
                            $fieldKey => $fieldId,
                            $tableRowKey => $rowId,
                            $tableColumnKey => $columnId,
                            'text_value' => $textValue,
                            'number_value' => $numberValue,
                            'decimal_value' => $decimalValue,
                            'field_type' => $obj['field_type'],
                            'params' => $obj['params']
                        ];
                    }
                }
            }
        }

        $settings['tableCells'] = $tableCells;
    }
}
