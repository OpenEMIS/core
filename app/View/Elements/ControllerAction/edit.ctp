<?php
// Requires FormUtilityHelper and LabelHelper

$formDefaults = $this->FormUtility->getFormDefaults();

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
		'label' => true
	);
	$_attr = array_merge($_attr, $attr);
	$_type = $_attr['type'];
	$visible = $this->FormUtility->isFieldVisible($_attr, 'edit');
	
	if ($visible) {
		$_model = array_key_exists('model', $_attr) ? $_attr['model'] : $model;
		$_model = $_attr['model'];
		$fieldName = $_model . '.' . $_field;
		$options = isset($_attr['attr']) ? $_attr['attr'] : array();
		
		$label = $this->Label->getLabel2($_model, $_field, $_attr);
		if (!empty($label)) {
			$options['label'] = $formDefaults['label'];
			$options['label']['text'] = $label;
		}
		
		switch ($_type) {
			case 'disabled': 
				$options['type'] = 'text';
				$options['disabled'] = 'disabled';
				if (isset($_attr['options'])) {
					$options['value'] = $_attr['options'][$this->request->data[$_model][$_field]];
				}
				//echo $this->Form->hidden($fieldName);
				break;
				
			case 'select':
				if (isset($_attr['options'])) {
					if (empty($_attr['options'])) {
						$options['empty'] = isset($_attr['empty']) ? $_attr['empty'] : $this->Label->get('general.noData');
					} else {
						if (isset($_attr['default'])) {
							$options['default'] = $_attr['default'];
						} else {
							if (!empty($this->request->data)) {
								if(!empty($this->request->data[$_model][$_field])) {
									$options['default'] = $this->request->data[$_model][$_field];
								}
							}
						}
					}
					$options['options'] = $_attr['options'];
				}

				// get rid of options that obsolete and not the default
				if (!empty($_attr['options'])) {
					reset($_attr['options']);
					$first_key = key($_attr['options']);
					if (is_array($_attr['options'][$first_key])) {
						foreach ($options['options'] as $okey => $ovalue) {
							if ($ovalue['obsolete'] == '1') {
								if (!array_key_exists('default', $options) || $ovalue['value']!=$options['default']) {
									unset($options['options'][$okey]);
								}
							}
						}
					}
				}
				break;

			case 'string':
				$options['type'] = 'string';
				if (array_key_exists('length', $_attr)) {
					$options['maxlength'] = $_attr['length'];
				}
				break;
				
			case 'text':
				$options['type'] = 'textarea';
				break;
			
			case 'hidden':
				$options['type'] = 'hidden';
				$options['label'] = false;
				$options['div'] = false;
				break;
				
			case 'element':
				$element = $_attr['element'];
				echo $this->element($element);
				break;
				
			case 'image':
				$attr = $_attr['attr'];
				$attr['field'] = $_field;
				$attr['label'] = $label;
				if (isset($this->data[$model][$_field . '_name']) && isset($this->data[$model][$_field])) {
					$attr['src'] = $this->Image->getBase64($this->data[$model][$_field . '_name'], $this->data[$model][$_field]);
				}
				echo $this->element('layout/file_upload_preview', $attr);
				break;
				
			case 'date':
				$attr = array('id' => $_model . '_' . $_field);
				if (array_key_exists($_model, $this->request->data)) {
					if (array_key_exists($_field, $this->request->data[$_model])) {
						$attr['data-date'] = $this->request->data[$_model][$_field];
					}
				}
				if (array_key_exists('attr', $_attr)) {
					$attr = array_merge($attr, $_attr['attr']);
				}

				$attr['label'] = $label;
				echo $this->FormUtility->datepicker($fieldName, $attr);
				break;
				
			case 'time':
				$attr = array('id' => $_model . '_' . $_field);

				if (array_key_exists('attr', $_attr)) {
					$attr = array_merge($attr, $_attr['attr']);
				}

				if (array_key_exists($_model, $this->request->data)) {
					if (array_key_exists($_field, $this->request->data[$_model])) {
						$attr['value'] = $this->request->data[$_model][$_field];
					}
				}

				$attr['label'] = $label;
				echo $this->FormUtility->timepicker($fieldName, $attr);
				break;
			case 'file':
				echo $this->element('layout/attachment');
				break;
			case 'file_upload';
				$attr = array('field' => $_field);
				echo $this->element('layout/attachment_upload', $attr);
				break;
			default:
				break;
			
		}
		if (array_key_exists('dataModel', $_attr) && array_key_exists('dataField', $_attr)) {
			$dataModel = $_attr['dataModel'];
			$dataField = $_attr['dataField'];
			$options['value'] = $this->request->data[$dataModel][$dataField];
		} else if (isset($_attr['value'])) {
			$options['value'] = $_attr['value'];
		}
		if (!in_array($_type, array('image', 'date', 'time', 'file', 'file_upload', 'element'))) {
			echo $this->Form->input($fieldName, $options);
		}
	}
}
?>
