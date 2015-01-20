<?php if ($viewType == 'list') : ?>
	<div class="panel panel-default panel-custom">
		<div class="panel-heading"><?php echo $obj[$model]['name']; ?></div>
		<div class="panel-body">
		<?php
			if(count($obj[$modelOption]) > 0) {
				$modelId = $obj[$model]['id'];
				if(isset($dataValues[$modelId][0]['int_value'])) {
					$value = $dataValues[$modelId][0]['int_value'];
				} else {
					$value = isset($dataValues[$modelId][0]['value']) ? $dataValues[$modelId][0]['value'] : "";
				}
				if($action == 'view') {
					foreach($obj[$modelOption] as $dropdownValue) {
						echo ($value == $dropdownValue['id'] ? $dropdownValue['value'] : "");
					}
				} else {
					$dropdownOptions = array();
					foreach($obj[$modelOption] as $dropdownValue) {
						$dropdownOptions[$dropdownValue['id']] = $dropdownValue['value'];
					}
					echo $this->Form->hidden("$modelValue.dropdown.$modelId.type", array('value' => $obj[$model]['type']));
					if(isset($obj[$model]['is_mandatory'])) {
						echo $this->Form->hidden("$modelValue.dropdown.$modelId.is_mandatory", array('value' => $obj[$model]['is_mandatory']));
					}
					if(isset($obj[$model]['is_unique'])) {
						echo $this->Form->hidden("$modelValue.dropdown.$modelId.is_unique", array('value' => $obj[$model]['is_unique']));
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
<?php else : ?>
	<?
		$modelId = $obj[$model]['id'];
		if(isset($dataValues[$modelId][0]['int_value'])) {
			$value = $dataValues[$modelId][0]['int_value'];
		} else {
			$value = isset($dataValues[$modelId][0]['value']) ? $dataValues[$modelId][0]['value'] : "" ;			
		}
		$dropdownOptions = array();
		foreach($obj[$modelOption] as $dropdownValue) {
			$dropdownOptions[$dropdownValue['id']] = $dropdownValue['value'];
		}

		$labelOptions = array('text' => $obj[$model]['name'], 'class' => 'col-md-3 control-label');
		echo $this->Form->input("$modelValue.dropdown.$modelId.value", array(
			'class' => 'form-control',
			'div' => 'row form-group',
			'between' => '<div class="col-md-4">',
			'after' => '</div>',
			'label' => $labelOptions,
			'options' => $dropdownOptions,
			'selected' => $value
		));
	?>
<?php endif ?>
