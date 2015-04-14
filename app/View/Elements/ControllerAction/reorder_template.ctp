<?php
echo $this->Html->script('reorder', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), $_buttons['back']['url'], array('class' => 'divider'));
$this->end();

$this->start('contentBody');
	echo $this->element('ControllerAction/reorder');
$this->end();
?>
