<div class="panel panel-default panel-custom">
	<div class="panel-heading">
		<?php
			echo $obj[$model]['name'];
			echo $this->element('customfields/mandatory', compact('obj'));
		?>
	</div>
	<div class="panel-body">
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
			echo $this->Form->hidden("$modelValue.textarea.$modelId.type", array('value' => $obj[$model]['type']));
			if(isset($obj[$model]['is_mandatory'])) {
				echo $this->Form->hidden("$modelValue.textarea.$modelId.is_mandatory", array('value' => $obj[$model]['is_mandatory']));
			}
			if(isset($obj[$model]['is_unique'])) {
				echo $this->Form->hidden("$modelValue.textarea.$modelId.is_unique", array('value' => $obj[$model]['is_unique']));
			}
			echo $this->Form->input("$modelValue.textarea.$modelId.value", array(
				'type' => 'textarea',
				'class' => 'form-control',
				'div' => false,
				'label' => false,
				'error' => false,
				'before' => false,
				'between' => false,
				'after' => false,
				'value' => $value
			));
			$customFieldName = "$modelValue.$modelId.textarea_value";
			$error = $this->Form->isFieldError($customFieldName) ? $this->Form->error($customFieldName) : '';
			echo $error;
		}
	?>
	</div>
</div>
