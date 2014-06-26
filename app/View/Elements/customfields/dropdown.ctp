<div class="custom_field">
	<div class="field_label"><?php echo $obj[$model]['name']; ?></div>
	<div class="field_value">
	<?php
		if(count($obj[$modelOption]) > 0) {
			$modelId = $obj[$model]['id'];
			$value = isset($dataValues[$modelId][0]['value']) ? $dataValues[$modelId][0]['value'] : "";
			if($action == 'view') {
				foreach($obj[$modelOption] as $dropdownValue) {
					echo ($value == $dropdownValue['id'] ? $dropdownValue['value'] : "");
				}
			} else {
				$dropdownOptions = array();
				foreach($obj[$modelOption] as $dropdownValue) {
					$dropdownOptions[$dropdownValue['id']] = $dropdownValue['value'];
				}
				echo $this->Form->input("$modelValue.dropdown.$modelId.value", array(
					'class' => 'form-control',
					'div' => false,
					'label' => false,
					'options' => $dropdownOptions,
					'selected' => $value
				));
			}
		}
	?>
	</div>
</div>
