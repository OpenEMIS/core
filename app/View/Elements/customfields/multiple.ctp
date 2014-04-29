<div class="custom_field">
	<div class="field_label"><?php echo $obj[$model]['name']; ?></div>
	<div class="field_value">
	<?php
		$defaults = array();
		$modelId = $obj[$model]['id'];
		if(count($obj[$modelOption]) > 0) {
			foreach($obj[$modelOption] as $dropdownValue) {
				if(isset($dataValues[$modelId]) && count($dataValues[$modelId] > 0)){
					foreach($dataValues[$modelId] as $checkboxValue) {
						$defaults[] = $checkboxValue['value'];
					}
				}
				echo '<input type="checkbox" disabled '.(in_array($dropdownValue['id'], $defaults) ? 'checked' : "" ).'> <label>'.$dropdownValue['value'].'</label> ';
			}
		}
	?>
	</div>
</div>
