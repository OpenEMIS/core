<div class="panel panel-default panel-custom">
	<div class="panel-heading"><?php echo $obj[$model]['name']; ?></div>
	<div class="panel-body">
	<?php
		$defaults = array();
		$checkboxOptions = array('type' => 'checkbox', 'label' => false, 'div' => false, 'before' => false, 'between' => false, 'after' => false, 'class' => false);
		if(count($obj[$modelOption]) > 0) {
			$modelId = $obj[$model]['id'];
			$counter = 0;
			echo $this->Form->hidden("$modelValue.checkbox.$modelId.type", array('value' => $obj[$model]['type']));
			foreach($obj[$modelOption] as $dropdownValue) {
				if(isset($dataValues[$modelId]) && count($dataValues[$modelId] > 0)){
					foreach($dataValues[$modelId] as $checkboxValue) {
						if(isset($checkboxValue['text_value'])) {
							$defaults[] = $checkboxValue['text_value'];
						} else {
							$defaults[] = isset($checkboxValue['value']) ? $checkboxValue['value'] : "";
						}
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
