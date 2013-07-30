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
		echo $this->Form->input('visible', $inputOpts);
		?>
	</div>
	<div class="cell cell_name">
		<?php
		$inputOpts = array(
			'label' => false,
			'div' => false,
			'name' => sprintf($fieldName, 'name'),
			'type' => 'text',
			'maxlength' => 50,
			'before' => '<div class="input_wrapper">',
			'after' => '</div>'
		);
		echo $this->Form->input('name', $inputOpts);
		?>
	</div>
    <div class="cell cell_national_code">
		<?php
		$inputOpts = array(
			'label' => false,
			'div' => false,
			'name' => sprintf($fieldName, 'national_code'),
			'type' => 'text',
			'maxlength' => 10,
			'before' => '<div class="input_wrapper">',
			'after' => '</div>'
		);
		echo $this->Form->input('name', $inputOpts);
		?>
	</div>
    <div class="cell cell_international_code">
		<?php
		$inputOpts = array(
			'label' => false,
			'div' => false,
			'name' => sprintf($fieldName, 'international_code'),
			'type' => 'text',
			'maxlength' => 10,
			'before' => '<div class="input_wrapper">',
			'after' => '</div>'
		);
		echo $this->Form->input('name', $inputOpts);
		?>
	</div>
	<div class="cell cell_order">
		<span class="icon_up" onclick="jsList.doSort(this)"></span>
		<span class="icon_down" onclick="jsList.doSort(this)"></span>
		<span class="icon_cross" onclick="jsList.doRemove(this)"></span>
	</div>
</li>