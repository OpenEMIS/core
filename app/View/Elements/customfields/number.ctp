<div class="custom_field">
	<div class="field_label"><?php echo $obj[$model]['name']; ?></div>
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
			echo $this->Form->input("$modelValue.number.$modelId.value", array(
				'class' => 'form-control',
				'div' => false,
				'label' => false,
				'onkeypress' => 'return utility.integerCheck(event)',
				'value' => $value
			));
		}
	?>
	</div>
</div>
