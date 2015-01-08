<div class="custom_field">
	<div class="field_label"><?php echo $obj[$model]['name']; ?></div>
	<div class="field_value">
	<?php
		$modelId = $obj[$model]['id'];
		if(isset($dataValues[$modelId][0]['textarea_value'])) {
			$value = $dataValues[$modelId][0]['textarea_value'];
		} else {
			$value = isset($dataValues[$modelId][0]['value']) ? $dataValues[$modelId][0]['value'] : "" ;
		}
		if($action == 'view') {
			echo $value;
		} else {
			echo $this->Form->input("$modelValue.textarea.$modelId.value", array(
				'type' => 'textarea',
				'class' => 'form-control',
				'div' => false,
				'label' => false,
				'before' => false,
				'between' => false,
				'after' => false,
				'value' => $value
			));
		}
	?>
	</div>
</div>
