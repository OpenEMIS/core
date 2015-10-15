<?php
	$alias = $ControllerAction['table']->alias();
	$classOptions = $attr['attr']['options'];
	$inputOptions = [
		'type' => 'select',
		'label' => false,
		'options' => $classOptions,
		'onchange' => '$("#reload").val("changeClass").click();return false;'
	];
	echo $this->Form->input("$alias.class", $inputOptions);
?>
