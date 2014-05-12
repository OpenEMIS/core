<?php
$defaults = $this->FormUtility->getFormDefaults();
foreach($fields['fields'] as $field) {
	$fieldType = isset($field['type']) ? $field['type'] : 'text';
	$editable = (!isset($field['edit']) || $field['edit']!==false);
	if($editable) {
		$key = $field['field'];
		$model = isset($field['model']) ? $field['model'] : $fields['model'];
		$label = $this->Label->getLabel($model, $field);
		$fieldName = $model . '.' . $key;
		$options = array();
		$options['label'] = array('text' => $label, 'class' => $defaults['label']['class']);
		$options['type'] = $fieldType;
		$value = '';
		
		if($fieldType === 'select') { // dropdown list
			if(isset($field['options'])) {
				$options['options'] = $field['options'];
			}
			if(isset($field['default'])) {
				$options['default'] = $field['default'];
			}
			if(!empty($this->request->data)) {
				/*
				if(!empty($this->request->data[$field['model']][$key])) {
					$options['default'] = $this->request->data[$field['model']][$key];
				}*/
			}
		} else if($fieldType === 'textarea') {
			$options['type'] = 'textarea';
		} else if($fieldType === 'hidden') {
			$options['type'] = 'hidden';
			$options['label'] = false;
			$options['div'] = false;
		}
		echo $this->Form->input($fieldName, $options);
	}
}
?>
