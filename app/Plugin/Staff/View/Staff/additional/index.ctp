<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
if ($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'additionalEdit'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$model = 'StaffCustomField';
$modelOption = 'StaffCustomFieldOption';
$action = 'view';
echo $this->element('customfields/index', compact('model', 'modelOption', 'action'));
$this->end();
?>
