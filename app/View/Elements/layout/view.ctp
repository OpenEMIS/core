<?php
foreach($fields as $field) {
	$value = '';
	$key = $field['field'];
	$label = isset($field['label']) ? __($field['label']) : Inflector::humanize($key);
	
	if(!isset($field['view']) || $field['view']!==false)) { // allow display
		$fieldType = $field['type'];
		
		if($fieldType === 'link') { // is a hyperlink
			$labelText = $data[$field['model']][$field['link']['label_text']];
			$labelAction = $field['link']['label_action'];
			$labelParam = $field['link']['label_param'];
			$linkParam = '';
			foreach($labelParam as $l){
				$linkParam .= $data[$field['model']][$l];
			}
			$value = $this->Html->link($labelText, array("controller" => $this->params['controller'], "action" => $labelAction, $linkParam));
		} else if($fieldType === 'file') { // downloadable
			$value = $this->Html->link($data[$field['model']][$key], array(
					'controller' => $this->params['controller'],
					'action' => $field['action'], 
					$data[$field['model']]['id']
				),
				array('target' => '_self','escape' => false)
			);
		} else if($fieldType === 'select') { // dropdown list
			
		} else {
			$value = $field['options'][$data[$field['model']][$key]];
		}
		echo '<div class="row">';
		echo '<div class="col-md-2">' . $label . '</div>';
		echo '<div class="col-md-6">' . $value . '</div>';
		echo '</div>';
	}
}
?>
