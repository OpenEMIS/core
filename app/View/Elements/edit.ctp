<?php
$defaults = $this->FormUtility->getFormDefaults();

foreach($fields as $key => $field) {
	$fieldType = isset($field['type']) ? $field['type'] : 'string';
	$visible = $this->FormUtility->isFieldVisible($field, 'edit');
	
	if ($visible) {
		$fieldModel = array_key_exists('model', $field) ? $field['model'] : $model;
		$fieldName = $fieldModel . '.' . $key;
		$options = array();
		$label = $this->Label->getLabel2($fieldModel, $key, $field);
		if(!empty($label)) {
			$options['label'] = array('text' => $label, 'class' => $defaults['label']['class']);
		}
		
		switch ($fieldType) {
			case 'disabled': 
				$options['type'] = 'text';
				$options['disabled'] = 'disabled';
				if (isset($field['options'])) {
					$options['value'] = $field['options'][$this->request->data[$fieldModel][$key]];
				}
				echo $this->Form->hidden($fieldName);
				break;
				
			case 'select':
				if (isset($field['options'])) {
					$options['options'] = $field['options'];
				}
				if (isset($field['default'])) {
					$options['default'] = $field['default'];
				}
				if (!empty($this->request->data)) {
					if(!empty($this->request->data[$fieldModel][$key])) {
						$options['default'] = $this->request->data[$fieldModel][$key];
					}
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
				$element = $field['element'];
				echo $this->element($element);
				break;
				
			case 'image':
				$attr = $field['attr'];
				$attr['field'] = $key;
				$attr['label'] = $label;
				if (isset($this->data[$model][$key . '_name']) && isset($this->data[$model][$key])) {
					$attr['src'] = $this->Image->getBase64($this->data[$model][$key . '_name'], $this->data[$model][$key]);
				}
				echo $this->element('layout/file_upload_preview', $attr);
				break;
				
			case 'date':
				$attr = array('id' => $fieldModel . '_' . $key);
				if (array_key_exists($fieldModel, $this->request->data)) {
					if (array_key_exists($key, $this->request->data[$fieldModel])) {
						$attr['data-date'] = $this->request->data[$fieldModel][$key];
					}
				}
				if (array_key_exists('attr', $field)) {
					$attr = array_merge($attr, $field['attr']);
				}
				echo $this->FormUtility->datepicker($fieldName, $attr);
				break;
				
			case 'time':
				$attr = array('id' => $fieldModel . '_' . $key);
				
				if (array_key_exists('attr', $field)) {
					$attr = array_merge($dateOptions, $field['attr']);
				}
				echo $this->FormUtility->timepicker($fieldName, $attr);
				break;
			case 'file':
				echo $this->element('layout/attachment');
				break;
			case 'file_upload';
				$attr = array('field' => $key);
				echo $this->element('layout/attachment_upload', $attr);
				break;
			default:
				break;
			
		}
		if (isset($field['value'])) {
			$options['value'] = $field['value'];
		}
		if (!in_array($fieldType, array('image', 'date', 'time', 'file', 'file_upload', 'element'))) {
			echo $this->Form->input($fieldName, $options);
		}
	}
}
?>
