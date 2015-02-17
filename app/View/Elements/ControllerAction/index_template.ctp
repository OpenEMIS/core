<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model.title")));

$this->start('contentActions');
if ($_add) {
	$actionParams = $_triggerFrom == 'Controller' ? array('action' => 'add') : array('action' => $model, 'add');
    echo $this->Html->link($this->Label->get('general.add'), $actionParams, array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
	echo $this->element('ControllerAction/index');
$this->end();
