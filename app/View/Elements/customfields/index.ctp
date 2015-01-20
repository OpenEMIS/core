<?php
$customElement = array(
	1 => 'customfields/section_break',
	2 => 'customfields/text',
	3 => 'customfields/dropdown',
	4 => 'customfields/multiple',
	5 => 'customfields/textarea',
	6 => 'customfields/number',
	7 => 'customfields/table'
);

$elementOptions = compact('model', 'modelOption', 'action');
$elementOptions['modelValue'] = $action == 'edit' ? $modelValue : '';
$elementOptions['modelRow'] = isset($modelRow) ? $modelRow : '';
$elementOptions['modelColumn'] = isset($modelColumn) ? $modelColumn : '';
$elementOptions['modelCell'] = isset($modelCell) && $action == 'edit' ? $modelCell : '';
$elementOptions['viewType'] = isset($viewType) ? $viewType : 'list';

foreach($data as $obj) {
	$element = $customElement[$obj[$model]['type']];
	$elementOptions['obj'] = $obj;
	echo $this->element($element, $elementOptions);
}
?>
