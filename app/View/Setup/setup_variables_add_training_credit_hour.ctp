<?php
list($model, $order, $index, $conditions) = $params;
$fieldName = sprintf('data[%s][%s][%%s]', $model, $index);
?>
<li data-id="<?php echo ($index); ?>" class="new_row <?php echo $order%2==0 ? 'li_even' : ''; ?>">
	<?php
	echo $this->Form->hidden('id', array(
		'label' => false,
		'div' => false,
		'name' => sprintf($fieldName, 'id'),
		'value' => 0
	));
	echo $this->Form->hidden('order', array(
		'label' => false,
		'div' => false,
		'id' => 'order',
		'name' => sprintf($fieldName, 'order'),
		'value' => $order
	));
	
	foreach($conditions as $conditionName => $conditionValue) {
		echo $this->Form->hidden($conditionName, array('name' => sprintf($fieldName, $conditionName), 'value' => $conditionValue));
	}
	?>
	<div class="cell cell_visible">
		<?php
		$inputOpts = array(
			'label' => false,
			'div' => false,
			'name' => sprintf($fieldName, 'visible'),
			'type' => 'checkbox',
			'value' => 1,
			'autocomplete' => 'off',
			'onchange' => 'jsList.activate(this)',
			'checked' => 'checked'
		);
		echo $this->Form->input($model.'.'.$index.'.visible', $inputOpts);
		?>
	</div>
	
	<div class="cell cell_name">
		<div class="input_wrapper">
		<?php
		$inputOpts = array(
			'label' => false,
			'div' => false,
			'name' => sprintf($fieldName, 'name'),
			'readonly' => 'readonly',
			'onkeypress'=> "return utility.integerCheck(event)",
			'type' => 'text',
			'maxlength' => 50
		);
		echo $this->Form->input('SetupVariables..'.$index.'.name', $inputOpts);
		?>
		</div>
	</div>

	<div class="cell cell_national_code">
		<div class="input_wrapper">
		<?php
		$inputOpts = array(
			'label' => false,
			'div' => false,
			'name' => sprintf($fieldName, 'min'),
			'onkeypress'=> "return utility.integerCheck(event)",
			'onchange' => 'setCreditHourName("'. $index . '")',
			'type' => 'text',
			'maxlength' => 30
		);
		echo $this->Form->input('SetupVariables.'.$index.'.min', $inputOpts);
		?>
		</div>
	</div>

	<div class="cell cell_international_code">
		<div class="input_wrapper">
		<?php
		$inputOpts = array(
			'label' => false,
			'div' => false,
			'name' => sprintf($fieldName, 'max'),
			'onkeypress'=> "return utility.integerCheck(event)",
			'onchange' => 'setCreditHourName("'. $index . '")',
			'type' => 'text',
			'maxlength' => 30
		);
		echo $this->Form->input('SetupVariables.'.$index.'.max', $inputOpts);
		?>
		</div>
	</div>
	
	<div class="cell cell_order">
		<span class="icon_up" onclick="jsList.doSort(this)"></span>
		<span class="icon_down" onclick="jsList.doSort(this)"></span>
		<span class="icon_cross" onclick="jsList.doRemove(this)"></span>
	</div>
</li>