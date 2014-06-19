<?php
$customElement = array(
	1 => 'customfields/label',
	2 => 'customfields/text',
	3 => 'customfields/dropdown',
	4 => 'customfields/multiple',
	5 => 'customfields/textarea'
);

$elementOptions = compact('model', 'modelOption', 'action');
$elementOptions['modelValue'] = $action == 'edit' ? $modelValue : '';

foreach($data as $obj) {
	$element = $customElement[$obj[$model]['type']];
	$elementOptions['obj'] = $obj;
	echo $this->element($element, $elementOptions);
}
?>
