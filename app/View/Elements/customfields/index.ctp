<?php
$customElement = array(
	1 => 'customFields/label',
	2 => 'customFields/text',
	3 => 'customFields/dropdown',
	4 => 'customFields/multiple',
	5 => 'customFields/textarea'
);

$elementOptions = compact('model', 'modelOption', 'action');
if($action == 'edit') {
	$elementOptions['modelValue'] = $modelValue;
}
foreach($data as $obj) {
	$element = $customElement[$obj[$model]['type']];
	$elementOptions['obj'] = $obj;
	echo $this->element($element, $elementOptions);
}
?>
