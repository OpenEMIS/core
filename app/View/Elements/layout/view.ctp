<?php
foreach($fields['fields'] as $field) {
	$fieldType = isset($field['type']) ? $field['type'] : 'text';
	$viewable = (!isset($field['view']) || $field['view']!==false) && $fieldType !== 'hidden';
	if($viewable) {
		$key = $field['field'];
		$model = isset($field['model']) ? $field['model'] : $fields['model'];
		$label = $this->Label->getLabel($model, $field);
		
		if(array_key_exists($key, $data[$model])) {
			$value = $data[$model][$key];
		
			if($fieldType === 'file') { // is a hyperlink
				$linkOptions = $field['url'];
				$linkOptions[] = $data[$model]['id'];
				$value = $this->Html->link($value, $linkOptions);
			} else if($fieldType === 'select') { // dropdown list
				if(isset($field['options'])) {
					$value = $field['options'][$data[$model][$key]];
				}
			}
			
			echo '<div class="row">';
			echo '<div class="col-md-3">' . $label . '</div>';
			echo '<div class="col-md-6">' . $value . '</div>';
			echo '</div>';
		} else {
			if($key == 'modified_by' || $key == 'created_by') {
				$value = trim($data[$model]['first_name'] . ' ' . $data[$model]['last_name']);
				echo '<div class="row">';
				echo '<div class="col-md-3">' . $label . '</div>';
				echo '<div class="col-md-6">' . $value . '</div>';
				echo '</div>';
			} else {
				$msg = 'URL [%s/%s] -> Field [%s] does not exist in Model [%s]';
				$msg = sprintf($msg, $this->params['controller'], $this->action, $key, $model);
				$this->log($msg, 'debug');
			}
		}
	}
}
?>
