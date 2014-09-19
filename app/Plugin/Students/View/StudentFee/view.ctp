<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('StudentFee.title'));

$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => 'StudentFee'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
	echo $this->element('view');
$this->end();
?>
