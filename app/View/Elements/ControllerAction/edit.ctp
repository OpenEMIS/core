<?php
//ControllerActionComponent - Version 1.0.3
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

$_attrDefaults = array(
	'type' => 'string',
	'model' => $model,
	'label' => true
);

foreach ($displayFields as $_field => $attr) {
	$_fieldAttr = array_merge($_attrDefaults, $attr);
	$_type = $_fieldAttr['type'];
	$visible = $this->FormUtility->isFieldVisible($_fieldAttr, 'edit');
	
	if ($visible) {
		$_fieldModel = array_key_exists('model', $_fieldAttr) ? $_fieldAttr['model'] : c;
		$_fieldModel = $_fieldAttr['model'];
		$fieldName = $_fieldModel . '.' . $_field;
		$options = isset($_fieldAttr['attr']) ? $_fieldAttr['attr'] : array();
		
		$label = $this->Label->getLabel2($_fieldModel, $_field, $_fieldAttr);
		if (!empty($label)) {
			$options['label'] = $formDefaults['label'];
			$options['label']['text'] = $label;
		}
		
		switch ($_type) {
			case 'disabled': 
				$options['type'] = 'text';
				$options['disabled'] = 'disabled';
				if (isset($_fieldAttr['options'])) {
					$options['value'] = $_fieldAttr['options'][$this->request->data[$_fieldModel][$_field]];
				}
				//echo $this->Form->hidden($fieldName);
				break;
				
			case 'select':
				if (isset($_fieldAttr['options'])) {
					if (empty($_fieldAttr['options'])) {
						$options['empty'] = isset($_fieldAttr['empty']) ? $_fieldAttr['empty'] : $this->Label->get('general.noData');
					} else {
						if (isset($_fieldAttr['default'])) {
							$options['default'] = $_fieldAttr['default'];
						} else {
							if (!empty($this->request->data)) {
								if(!empty($this->request->data[$_fieldModel][$_field])) {
									$options['default'] = $this->request->data[$_fieldModel][$_field];
								}
							}
						}
					}
					$options['options'] = $_fieldAttr['options'];
				}

				// get rid of options that obsolete and not the default
				if (!empty($_fieldAttr['options'])) {
					reset($_fieldAttr['options']);
					$first_key = key($_fieldAttr['options']);
					if (is_array($_fieldAttr['options'][$first_key])) {
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
				if (array_key_exists('length', $_fieldAttr)) {
					$options['maxlength'] = $_fieldAttr['length'];
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
				$element = $_fieldAttr['element'];
				echo $this->element($element);
				break;
				
			case 'image':
				$attr = $_fieldAttr['attr'];
				$attr['field'] = $_field;
				$attr['label'] = $label;
				if (isset($this->data[$model][$_field . '_name']) && isset($this->data[$model][$_field])) {
					$attr['src'] = $this->Image->getBase64($this->data[$model][$_field . '_name'], $this->data[$model][$_field]);
				}
				echo $this->element('layout/file_upload_preview', $attr);
				break;
				
			case 'date':
				$attr = array('id' => $_fieldModel . '_' . $_field);
				if (array_key_exists($_fieldModel, $this->request->data)) {
					if (array_key_exists($_field, $this->request->data[$_fieldModel])) {
						$attr['data-date'] = $this->request->data[$_fieldModel][$_field];
					}
				}
				if (array_key_exists('attr', $_fieldAttr)) {
					$attr = array_merge($attr, $_fieldAttr['attr']);
				}

				$attr['label'] = $label;
				echo $this->FormUtility->datepicker($fieldName, $attr);
				break;
				
			case 'time':
				$attr = array('id' => $_fieldModel . '_' . $_field);

				if (array_key_exists('attr', $_fieldAttr)) {
					$attr = array_merge($attr, $_fieldAttr['attr']);
				}

				if (array_key_exists($_fieldModel, $this->request->data)) {
					if (array_key_exists($_field, $this->request->data[$_fieldModel])) {
						$attr['value'] = $this->request->data[$_fieldModel][$_field];
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

			case 'chosen_select':
				echo $this->Html->css('../js/plugins/chosen/chosen.min', 'stylesheet', array('inline' => false));
				echo $this->Html->script('plugins/chosen/chosen.jquery.min', array('inline' => false));
				$options['options'] = isset($attr['options']) ? $attr['options'] : array();
				$options['class'] = 'chosen-select';
				$options['multiple'] = true;
				$options['data-placeholder'] = isset($attr['placeholder']) ? $attr['placeholder'] : '';
				$fieldName = $attr['id'];
				break;

			default:
				break;
			
		}
		if (array_key_exists('dataModel', $_fieldAttr) && array_key_exists('dataField', $_fieldAttr)) {
			$dataModel = $_fieldAttr['dataModel'];
			$dataField = $_fieldAttr['dataField'];
			$options['value'] = $this->request->data[$dataModel][$dataField];
		} else if (isset($_fieldAttr['value'])) {
			$options['value'] = $_fieldAttr['value'];
		}
		if (!in_array($_type, array('image', 'date', 'time', 'file', 'file_upload', 'element'))) {
			echo $this->Form->input($fieldName, $options);
		}
	}
}
?>
