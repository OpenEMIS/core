<div class="custom_field">
	<div class="field_label"><?php echo $obj[$model]['name']; ?></div>
	<div class="field_value">
	<?php
		$defaults = array();
		$checkboxOptions = array('type' => 'checkbox', 'label' => false, 'div' => false, 'before' => false, 'between' => false, 'after' => false, 'class' => false);
		if(count($obj[$modelOption]) > 0) {
			$modelId = $obj[$model]['id'];
			$counter = 0;
			foreach($obj[$modelOption] as $dropdownValue) {
				if(isset($dataValues[$modelId]) && count($dataValues[$modelId] > 0)){
					foreach($dataValues[$modelId] as $checkboxValue) {
						$defaults[] = $checkboxValue['value'];
					}
				}
				$checkboxOptions['checked'] = in_array($dropdownValue['id'], $defaults) ? 'checked' : '';
				if($action == 'view') {
					$checkboxOptions['disabled'] = 'disabled';
				} else {
					$checkboxOptions['value'] = $dropdownValue['id'];
				}
				echo $this->Form->input("$modelValue.checkbox.$modelId.value.$counter", $checkboxOptions);
				echo '<label style="margin: 0 10px 0 5px;">'.$dropdownValue['value'].'</label>';
				$counter++;
			}
		}
	?>
	</div>
</div>
