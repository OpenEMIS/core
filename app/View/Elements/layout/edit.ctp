<?php
$defaults = $this->FormUtility->getFormDefaults();
foreach($fields['fields'] as $field) {
	$model = isset($field['model']) ? $field['model'] : $fields['model'];
	$key = $field['field'];
	$fieldName = $model . '.' . $key;
	$label = isset($field['label']) ? $field['label'] : Inflector::humanize($key);
	$options = array('label' => false, 'div' => false, 'class' => 'default');
	/*
	if(!empty($label)) {
		$options['label'] = array('text' => $label, 'class' => $defaults['label']['class']);
	}*/
	$value = '';
	if(!isset($field['edit']) || $field['edit'] !== false) {
		$fieldType = isset($field['type']) ? $field['type'] : 'text';
		if($fieldType === 'select') { // dropdown list
			$options['type'] = $fieldType;
			$options = array_merge($options, array('options' => $field['options']));
			if(!empty($this->request->data)) {
				/*
				if(!empty($this->request->data[$field['model']][$key])) {
					$options['default'] = $this->request->data[$field['model']][$key];
				}*/
			}
		} else if($fieldType === 'textarea') {
			$options['type'] = $fieldType;
		} else if($fieldType === 'hidden') {
			$options['type'] = 'hidden';
			echo $this->Form->input($fieldName, $options);
			continue;
		}
		$value = $this->Form->input($fieldName, $options);
		
		echo '<div class="row">';
		echo '<div class="label">' . __($label) . '</div>';
		echo '<div class="value">' . $value . '</div>';
		echo '</div>';
	}
}
?>
