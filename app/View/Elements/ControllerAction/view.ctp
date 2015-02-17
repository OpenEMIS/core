<?php
// Requires FormUtilityHelper and LabelHelper

$formDefaults = $this->FormUtility->getFormDefaults();

$html = '';
$row = '<div class="%s">%s</div>';
$_rowClass = array('row');

$_labelCol = '<div class="%s">%s</div>';
$_labelClass = array('col-md-3'); // default bootstrap class for labels

$_valueCol = '<div class="%s">%s</div>';
$_valueClass = array('col-md-6'); // default bootstrap class for values

$allowTypes = array('element', 'disabled');

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

foreach ($displayFields as $_field => $attr) {
	$_attr = array(
		'type' => 'string',
		'model' => $model,
		'label' => true,
		'rowClass' => '',
		'labelClass' => '',
		'valueClass' => ''
	);
	$_attr = array_merge($_attr, $attr);
	$_type = $_attr['type'];
	$visible = $this->FormUtility->isFieldVisible($_attr, 'view');

	if ($visible && $_type != 'hidden') {
		$_model = $_attr['model'];
		
		$label = $this->Label->getLabel2($_model, $_field, $_attr);
		$options = array();
		if (!empty($label)) {
			$options['label'] = $formDefaults['label'];
			$options['label']['text'] = $label;
		}
		
		if (array_key_exists($_field, $data[$_model]) || in_array($_type, $allowTypes)) {
			$value = '';
			if (array_key_exists('dataModel', $_attr) && array_key_exists('dataField', $_attr)) {
				$dataModel = $_attr['dataModel'];
				$dataField = $_attr['dataField'];
				$value = $data[$dataModel][$dataField];
			} else if (isset($data[$_model][$_field])) {
				$value = $data[$_model][$_field];
			}
			
			switch ($_type) {
				case 'disabled':
					$value = $_attr['value'];
					break;
					
				case 'select':
					if (!empty($_attr['options'])) {
						reset($_attr['options']);
						$firstKey = key($_attr['options']);
						if (is_array($_attr['options'][$firstKey])) {
							foreach ($_attr['options'] as $fkey => $fvalue) {
								if ($fvalue['value'] == $value) {
									$value = $fvalue['name'];
								}
							}
						} else {
							if (array_key_exists($value, $_attr['options'])) {
								$value = $_attr['options'][$value];
							}
						}
					}
					break;

				case 'text':
					$value = nl2br($value);
					break;

				case 'image':
					//$value = $this->Image->getBase64Image($data[$model][$_field . '_name'], $data[$model][$_field], $_attr['attr']);
					break;
					
				case 'download':
					$value = $this->Html->link($value, $_attr['attr']['url']);
					break;
					
				case 'element':
					$element = $_attr['element'];
					if (array_key_exists('class', $_attr)) {
						$class = $_attr['class'];
					}
					$value = $this->element($element);
					break;
					
				case 'date':
					$value = $this->Utility->formatDate($value, null, false);
					break;
				
				case 'modified_user_id':
				case 'created_user_id':
					$dataModel = $_attr['dataModel'];
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

			if (!empty($_attr['rowClass'])) {
				$_rowClass[] = $_attr['rowClass'];
			}
			if (!empty($_attr['labelClass'])) {
				$_labelClass[] = $_attr['labelClass'];
			}
			if (!empty($_attr['valueClass'])) {
				$_valueClass[] = $_attr['valueClass'];
			}

			$valueClass = implode(' ', $_valueClass);
			$rowClass = implode(' ', $_rowClass);

			if ($_attr['label']) {
				$labelClass = implode(' ', $_labelClass);
				$rowContent = sprintf($_labelCol.$_valueCol, $labelClass, $label, $valueClass, $value);
			} else { // no label
				$rowContent = sprintf($valueCol, $valueClass, $value);
			}
			$html .= sprintf($row, $rowClass, $rowContent);
		} else {
			pr(sprintf('Field [%s] does not exist in Model [%s]', $_field, $_model));
		}
	}
}
echo $html;
?>
