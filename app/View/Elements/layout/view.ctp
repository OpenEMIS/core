<?php
foreach($fields['fields'] as $field) {
	$model = isset($field['model']) ? $field['model'] : $fields['model'];
	$value = '';
	$key = $field['field'];
	$label = isset($field['label']) ? __($field['label']) : Inflector::humanize($key);
	
	if(!isset($field['view']) || $field['view']!==false) { // allow display
		$fieldType = isset($field['type']) ? $field['type'] : 'text';
		
		if($fieldType === 'link') { // is a hyperlink
			
		} else if($fieldType === 'file') { // downloadable
			
		} else if($fieldType === 'select') { // dropdown list
			
		} else {
			if($key !== 'modified_by' && $key !== 'created_by') {
				$value = $data[$model][$key];
			} else {
				$value = trim($data[$model]['first_name'] . ' ' . $data[$model]['last_name']);
			}
		}
		
		echo '<div class="row">';
		echo '<div class="label">' . $label . '</div>';
		echo '<div class="value">' . $value . '</div>';
		echo '</div>';
		
		/*
		echo '<div class="row">';
		echo '<div class="col-md-2">' . $label . '</div>';
		echo '<div class="col-md-6">' . $value . '</div>';
		echo '</div>';
		*/
	}
}
?>
