<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('More'));

$this->start('contentActions');
if($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'additionalEdit'),	array('class' => 'divider')); 
}
$this->end();

$this->start('contentBody');
$modelOption = 'InstitutionSiteCustomFieldOption';
$action = 'view';
echo $this->element('customFields/index', compact('model', 'modelOption', 'action'));
$this->end();
?>
