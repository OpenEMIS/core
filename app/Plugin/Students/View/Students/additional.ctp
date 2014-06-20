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
$model = 'StudentCustomField';
$modelOption = 'StudentCustomFieldOption';
$action = 'view';
echo $this->element('customfields/index', compact('model', 'modelOption', 'action'));
$this->end();
?>
