<div class="custom_field">
	<div class="field_label"><?php echo $obj[$model]['name']; ?></div>
	<div class="field_value">
	<?php
		$modelId = $obj[$model]['id'];
		$value = isset($dataValues[$modelId][0]['value']) ? $dataValues[$modelId][0]['value'] : "" ;
		if($action == 'view') {
			echo $value;
		} else {
			echo $this->Form->input("$modelValue.textbox.$modelId.value", array(
				'class' => 'form-control',
				'div' => false,
				'label' => false,
				'value' => $value
			));
		}
	?>
	</div>
</div>
