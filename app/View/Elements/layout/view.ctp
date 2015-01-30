<?php
foreach($fields['fields'] as $field) {
	$fieldType = isset($field['type']) ? $field['type'] : 'text';
	$viewable = (!isset($field['view']) || $field['view']!==false) && $fieldType !== 'hidden';
	if($viewable) {
		$key = $field['field'];
		$model = isset($field['model']) ? $field['model'] : $fields['model'];
		$label = $this->Label->getLabel($model, $field);
		$label = __($label);
		
		$multiRecords = (isset($field['multi_records']) && is_bool($field['multi_records']))? $field['multi_records']: false;
		$multiRecordsField = isset($field['multi_record_field'])? $field['multi_record_field']: 'multi_records';
		$displayFormat = isset($field['format'])?$field['format']: NULL;
		if($multiRecords){
			//Setup Label
			$multiValue = ''; 
			//Process Content
			foreach ($data[$multiRecordsField] as $obj){
				if($fieldType === 'files') {
					$value = $obj[$model][$key];
					$linkOptions = $field['url'];
					$linkOptions[] = $obj[$model]['id'];
					$multiValue .= '<div>'.$this->Html->link($value, $linkOptions).'</div>';
				}
				
			}
			echo '<div class="row">';
			echo '<div class="col-md-3">' . $label . '</div>';
			echo '<div class="col-md-6">' . $multiValue . '</div>';
			echo '</div>';
		}
		else if(empty($data[$model])){
			echo '<div class="row">';
			echo '<div class="col-md-3">' . $label . '</div>';
			echo '<div class="col-md-6"></div>';
			echo '</div>';
		}
		else if(array_key_exists($key, $data[$model])) {
				$value = $data[$model][$key];

				if($fieldType === 'file') { // is a hyperlink
					$linkOptions = $field['url'];
					$linkOptions[] = $data[$model]['id'];
					$value = $this->Html->link($value, $linkOptions);
				} else if($fieldType === 'select') { // dropdown list
					if(isset($field['options'])&&isset($field['options'][$data[$model][$key]])) {
						$value = $field['options'][$data[$model][$key]];
					}
				}
				else{
					$value = nl2br($value);
				}

				echo '<div class="row">';
				echo '<div class="col-md-3">' . $label . '</div>';
				echo '<div class="col-md-6">' . $value . '</div>';
				echo '</div>';
			
		} else {
			if($displayFormat == 'name'){
				$value = trim($data[$model]['first_name'] . ' ' . $data[$model]['last_name']);
				echo '<div class="row">';
				echo '<div class="col-md-3">' . $label . '</div>';
				echo '<div class="col-md-6">' . $value . '</div>';
				echo '</div>';
			}
			else if($key == 'modified_by' || $key == 'created_by') {
				$value = trim($data[$model]['first_name'] . ' ' . $data[$model]['last_name']);
				echo '<div class="row">';
				echo '<div class="col-md-3">' . $label . '</div>';
				echo '<div class="col-md-6">' . $value . '</div>';
				echo '</div>';
			}
			else {
				$msg = 'URL [%s/%s] -> Field [%s] does not exist in Model [%s]';
				$msg = sprintf($msg, $this->params['controller'], $this->action, $key, $model);
				$this->log($msg, 'debug');
			}
		}
	}
}
?>
