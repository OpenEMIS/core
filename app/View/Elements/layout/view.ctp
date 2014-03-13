<?php
foreach($fields as $field) {
	$value = '';
	$key = $field['field'];
	$label = isset($field['label']) ? __($field['label']) : Inflector::humanize($key);

	if(!isset($field['edit']) || $field['edit'] === false && (!isset($field['view']) || $field['view']!==false)) {
		if(!isset($field['options'])) {
			if(isset($field['link'])){
				$label_text = $data[$field['model']][$field['link']['label_text']];
				$label_action = $field['link']['label_action'];
				$label_param = $field['link']['label_param'];
				$link_param = '';
				foreach($label_param as $l){
					$link_param .= $data[$field['model']][$l];
				}
				$value = $this->Html->link($label_text, array("controller" => $this->params['controller'], "action" => $label_action, $link_param));
			} else if($key !== 'modified_by' && $key !== 'created_by') {
				if(isset($field['type']) && $field['type'] == 'download') { // for file download
					$value = $this->Html->link($data[$field['model']][$key], array(
							'controller' => $this->params['controller'],
							'action' => $field['action'], 
							$data[$field['model']]['id']
						),
						array('target' => '_self','escape' => false)
					);
				} else {
					$value = $data[$field['model']][$key];
				}
			} else {
				$value = trim($data[$field['model']]['first_name'] . ' ' . $data[$field['model']]['last_name']);
			}
		} else {
			/*
			if(isset($data[$field['model']]) && isset($data[$field['model']][$key])) {
				$value = $data[$field['model']][$key];
			} else {
				$value = $field['options'][$data[$field['model']][$key]];
			}
			*/
			$value = $field['options'][$data[$field['model']][$key]];
		}
		echo '<div class="row">';
		echo '<div class="col-md-2">' . $label . '</div>';
		echo '<div class="col-md-6">' . $value . '</div>';
		echo '</div>';
	}
}
?>
