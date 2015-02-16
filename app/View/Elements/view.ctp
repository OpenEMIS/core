<?php
$defaults = $this->FormUtility->getFormDefaults();

$html = '';
$row = '
<div class="row">
	<div class="col-md-3">%s</div>
	<div class="%s">%s</div>
</div>
';

$class = 'col-md-6';

foreach ($fields as $key => $field) {
	$fieldType = isset($field['type']) ? $field['type'] : 'string';
	$visible = $this->FormUtility->isFieldVisible($field, 'view');

	if ($visible && $fieldType != 'hidden') {
		$fieldModel = array_key_exists('model', $field) ? $field['model'] : $model;
		//$fieldName = $fieldModel . '.' . $key;
		$label = $this->Label->getLabel2($fieldModel, $key, $field);
		$options = array();
		if (!empty($label)) {
			$options['label'] = array('text' => $label, 'class' => $defaults['label']['class']);
		}
		
		if (array_key_exists($key, $data[$fieldModel]) || $fieldType == 'element' || $fieldType == 'disabled') {
			$value = '';
			if (array_key_exists('dataModel', $field) && array_key_exists('dataField', $field)) {
				$dataModel = $field['dataModel'];
				$dataField = $field['dataField'];
				$value = $data[$dataModel][$dataField];
			} else if (isset($data[$fieldModel][$key])) {
				$value = $data[$fieldModel][$key];
			}
			
			switch ($fieldType) {
				case 'disabled':
					$value = isset($field['value']) ? $field['value'] : $value;
					break;
					
				case 'select':
					if (!empty($field['options'])) {
						reset($field['options']);
						$first_key = key($field['options']);
						if (is_array($field['options'][$first_key])) {
							foreach ($field['options'] as $fkey => $fvalue) {
								if ($fvalue['value']==$value) {
									$value = $fvalue['name'];
								}
							}
						} else {
							if (array_key_exists($value, $field['options'])) {
								$value = $field['options'][$value];
							}
						}
					}
					break;

				case 'text':
					$value = nl2br($value);
					break;

				case 'image':
					//$value = $this->Image->getBase64Image($data[$model][$key . '_name'], $data[$model][$key], $field['attr']);
					break;
					
				case 'download':
					$value = $this->Html->link($value, $field['attr']['url']);
					break;
					
				case 'element':
					$element = $field['element'];
					if (array_key_exists('class', $field)) {
						$class = $field['class'];
					}
					$value = $this->element($element);
					break;
					
				case 'date':
					$value = $this->Utility->formatDate($value, null, false);
					break;

				case 'modified_user_id':
				case 'created_user_id':
					$dataModel = $field['dataModel'];
					if (isset($data[$dataModel]['first_name']) && isset($data[$dataModel]['last_name'])) {
						$value = $data[$dataModel]['first_name'] . ' ' . $data[$dataModel]['last_name'];
					}
					break;

				default:
					break;
			}

			if (is_string($value) && strlen(trim($value)) == 0) {
				$value = '&nbsp;';
			}
			if (!array_key_exists('override', $field)) {
				$html .= sprintf($row, $label, $class, $value);
			} else {
				$html .= '<div class="row">' . $value . '</div>';
			}
		} else {
			pr(sprintf('Field [%s] does not exist in Model [%s]', $key, $fieldModel));
		}
	}
}
echo $html;
?>
