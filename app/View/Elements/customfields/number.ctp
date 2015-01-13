<div class="custom_field">
	<div class="field_label">
		<?php
			echo $obj[$model]['name'];
			echo $this->element('customfields/mandatory', compact('obj'));
		?>
	</div>
	<div class="field_value">
	<?php
		$modelId = $obj[$model]['id'];
		if(isset($dataValues[$modelId][0]['int_value'])) {
			$value = $dataValues[$modelId][0]['int_value'];
		} else {
			$value = isset($dataValues[$modelId][0]['value']) ? $dataValues[$modelId][0]['value'] : "" ;			
		}
		if($action == 'view') {
			echo $value;
		} else {
			echo $this->Form->hidden("$modelValue.number.$modelId.type", array('value' => $obj[$model]['type']));
			if(isset($obj[$model]['is_mandatory'])) {
				echo $this->Form->hidden("$modelValue.number.$modelId.is_mandatory", array('value' => $obj[$model]['is_mandatory']));
			}
			if(isset($obj[$model]['is_unique'])) {
				echo $this->Form->hidden("$modelValue.number.$modelId.is_unique", array('value' => $obj[$model]['is_unique']));
			}
			echo $this->Form->input("$modelValue.number.$modelId.value", array(
				'class' => 'form-control',
				'div' => false,
				'label' => false,
				'error' => false,
				'onkeypress' => 'return utility.integerCheck(event)',
				'value' => $value
			));
			$customFieldName = "$modelValue.$modelId.int_value";
			$error = $this->Form->isFieldError($customFieldName) ? $this->Form->error($customFieldName) : '';
			echo $error;
		}
	?>
	</div>
</div>
