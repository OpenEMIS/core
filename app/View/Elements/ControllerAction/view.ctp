<?php
//ControllerActionComponent - Version 1.0.4
// Requires FormUtilityHelper and LabelHelper

$formDefaults = $this->FormUtility->getFormDefaults();

$html = '';
$row = '<div class="%s">%s</div>';
$_rowClass = array('row');

$_labelCol = '<div class="%s">%s</div>';
$_labelClass = 'col-md-3'; // default bootstrap class for labels

$_valueCol = '<div class="%s">%s</div>';
$_valueClass = 'col-md-6'; // default bootstrap class for values

$allowTypes = array('element', 'disabled', 'chosen_select');

$displayFields = $_fields;

if (isset($fields)) { // if we only want specific fields to be displayed
	foreach ($displayFields as $_field => $attr) {
		if (!in_array($displayFields, $fields)) {
			unset($displayFields[$_field]);
		}
	}
}

if (isset($exclude)) {
	foreach ($exclude as $f) {
		if (array_key_exists($f, $displayFields)) {
			unset($displayFields[$f]);
		}
	}
}

$_attrDefaults = array(
	'type' => 'string',
	'model' => $model,
	'label' => true,
	'rowClass' => '',
	'labelClass' => '',
	'valueClass' => ''
);

foreach ($displayFields as $_field => $attr) {
	$_fieldAttr = array_merge($_attrDefaults, $attr);
	$_type = $_fieldAttr['type'];
	$visible = $this->FormUtility->isFieldVisible($_fieldAttr, 'view');

	if ($visible && $_type != 'hidden') {
		$_fieldModel = $_fieldAttr['model'];
		
		$label = $this->Label->getLabel2($_fieldModel, $_field, $_fieldAttr);
		$options = array();
		if (!empty($label)) {
			$options['label'] = $formDefaults['label'];
			$options['label']['text'] = $label;
		}
		
		if (array_key_exists($_field, $data[$_fieldModel]) || in_array($_type, $allowTypes)) {
			$value = '';
			if (array_key_exists('dataModel', $_fieldAttr) && array_key_exists('dataField', $_fieldAttr)) {
				$dataModel = $_fieldAttr['dataModel'];
				$dataField = $_fieldAttr['dataField'];
				//$value = $data[$dataModel][$dataField];
				$value = isset($data[$dataModel][$dataField]) ? $data[$dataModel][$dataField] : '';
			} else if (isset($data[$_fieldModel][$_field])) {
				$value = $data[$_fieldModel][$_field];
			}
			
			switch ($_type) {
				case 'disabled':
					$value = $_fieldAttr['value'];
					break;
					
				case 'select':
					if (!empty($_fieldAttr['options'])) {
						reset($_fieldAttr['options']);
						$firstKey = key($_fieldAttr['options']);
						if (is_array($_fieldAttr['options'][$firstKey])) {
							foreach ($_fieldAttr['options'] as $fkey => $fvalue) {
								if ($fvalue['value'] == $value) {
									$value = $fvalue['name'];
								}
							}
						} else {
							if (array_key_exists($value, $_fieldAttr['options'])) {
								$value = $_fieldAttr['options'][$value];
							}
						}
					}
					break;

				case 'text':
					$value = nl2br($value);
					break;

				case 'image':
					//$value = $this->Image->getBase64Image($data[$model][$_field . '_name'], $data[$model][$_field], $_fieldAttr['attr']);
					break;
					
				case 'download':
					$value = $this->Html->link($value, $_fieldAttr['attr']['url']);
					break;
					
				case 'element':
					$element = $_fieldAttr['element'];
					if (array_key_exists('class', $_fieldAttr)) {
						$class = $_fieldAttr['class'];
					}
					$value = $this->element($element);
					break;
					
				case 'date':
					$value = $this->Utility->formatDate($value, null, false);
					break;

				case 'chosen_select':
					$_fieldAttr['dataModel'] = isset($_fieldAttr['dataModel']) ? $_fieldAttr['dataModel'] : Inflector::classify($_field);
					$_fieldAttr['dataField'] = isset($_fieldAttr['dataField']) ? $_fieldAttr['dataField'] : 'id';
					$_fieldAttr['dataSeparator'] = isset($_fieldAttr['dataSeparator']) ? $_fieldAttr['dataSeparator'] : ', ';
					$value = $this->element('ControllerAction/chosen_select', $_fieldAttr);
					break;
				
				case 'modified_user_id':
				case 'created_user_id':
					$dataModel = $_fieldAttr['dataModel'];
					if (isset($data[$dataModel]['first_name']) && isset($data[$dataModel]['last_name'])) {
						$value = $data[$dataModel]['first_name'] . ' ' . $data[$dataModel]['last_name'];
					}
					break;
					
				default:
					break;
			}

			if (is_string($value) && strlen(trim($value)) == 0) {
				$value = '&nbsp;';
			}

			if (!empty($_fieldAttr['rowClass'])) {
				$_rowClass[] = $_fieldAttr['rowClass'];
			}
			if (!empty($_fieldAttr['labelClass'])) {
				$_labelClass = $_fieldAttr['labelClass'];
			}
			if (!empty($_fieldAttr['valueClass'])) {
				$_valueClass = $_fieldAttr['valueClass'];
			}

			$valueClass = $_valueClass;
			$rowClass = implode(' ', $_rowClass);

			if ($_fieldAttr['label']) {
				$labelClass = $_labelClass;
				$rowContent = sprintf($_labelCol.$_valueCol, $labelClass, $label, $valueClass, $value);
			} else { // no label
				$rowContent = sprintf($_valueCol, $valueClass, $value);
			}
			$html .= sprintf($row, $rowClass, $rowContent);
		} else {
			pr(sprintf('Field [%s] does not exist in Model [%s]', $_field, $_fieldModel));
		}
	}
}
echo $html;
?>
