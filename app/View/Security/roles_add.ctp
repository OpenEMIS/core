<?php
$index = $order-1;
$fieldName = sprintf('data[SecurityRole][%s][%%s]', $index);
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