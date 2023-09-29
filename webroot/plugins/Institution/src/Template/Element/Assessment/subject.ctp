<?php
	$alias = $ControllerAction['table']->alias();
	$classOptions = $attr['attr']['options'];
	$inputOptions = [
		'type' => 'select',
		'label' => false,
		'options' => $classOptions,
		'onchange' => '$("#reload").val("changeSubject").click();return false;'
	];
	echo $this->Form->input("$alias.subject", $inputOptions);
	echo $this->Form->button('reload', ['id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden']);
?>
