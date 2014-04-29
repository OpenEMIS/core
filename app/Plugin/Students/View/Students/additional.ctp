<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('More'));

$this->start('contentActions');
if($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'additionalEdit'),	array('class' => 'divider')); 
}
echo $this->Html->link(__('Academic'), array('action' => 'custFieldYrView'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
$customElement = array(
	1 => 'customFields/label',
	2 => 'customFields/text',
	3 => 'customFields/dropdown',
	4 => 'customFields/multiple',
	5 => 'customFields/textarea'
);
$model = 'StudentCustomField';
$modelOption = 'StudentCustomFieldOption';
$action = 'view';
foreach($data as $obj) {
	$element = $customElement[$obj[$model]['type']];
	echo $this->element($element, compact('model', 'modelOption', 'obj', 'action'));
}
$this->end();
?>
