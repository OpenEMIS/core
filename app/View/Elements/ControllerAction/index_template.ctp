<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model.title")));

$this->start('contentActions');
	if ((isset($_add) && $_add) || !isset($_add)) {
	    echo $this->Html->link($this->Label->get('general.add'), $_buttons['add']['url'], array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
	echo $this->element('ControllerAction/index');
$this->end();
