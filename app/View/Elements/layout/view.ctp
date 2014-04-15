<?php
foreach($fields['fields'] as $field) {
	$fieldType = isset($field['type']) ? $field['type'] : 'text';
	$viewable = (!isset($field['view']) || $field['view']!==false) && $fieldType !== 'hidden';
	if($viewable) {
		$key = $field['field'];
		$model = isset($field['model']) ? $field['model'] : $fields['model'];
		$label = $this->Label->getLabel($model, $field);
		$value = '';
		
		if($fieldType === 'link') { // is a hyperlink
			
		} else if($fieldType === 'select') { // dropdown list
			$value = $field['options'][$data[$model][$key]];
		} else {
			if($key !== 'modified_by' && $key !== 'created_by') {
				$value = $data[$model][$key];
			} else {
				$value = trim($data[$model]['first_name'] . ' ' . $data[$model]['last_name']);
			}
		}
		
		echo '<div class="row">';
		echo '<div class="col-md-3">' . $label . '</div>';
		echo '<div class="col-md-6">' . $value . '</div>';
		echo '</div>';
	}
}
?>
