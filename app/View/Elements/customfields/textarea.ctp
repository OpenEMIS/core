<div class="custom_field">
	<div class="field_label"><?php echo $obj[$model]['name']; ?></div>
	<div class="field_value">
	<?php
		$modelId = $obj[$model]['id'];
		if($action == 'view') {
			if(isset($dataValues[$modelId][0]['value'])){
				echo $dataValues[$modelId][0]['value'];
			}
		} else {
			$value = '';
			if(isset($dataValues[$modelId][0]['value'])){
				$value = $dataValues[$modelId][0]['value'];
			}
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
