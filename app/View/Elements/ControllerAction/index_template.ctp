<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model.title")));

$this->start('contentActions');
	if ((isset($_add) && $_add) || !isset($_add)) {
	    echo $this->Html->link($this->Label->get('general.add'), $_buttons['add']['url'], array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
	if (isset($tabsElement)) {
		if (empty($this->params['plugin'])) {
			echo $this->element($tabsElement);
		} else {
			echo $this->element($tabsElement, array(), array('plugin' => $this->params['plugin']));
		}
	}

	if (isset($controlsElement)) {
		if (empty($this->params['plugin'])) {
			echo $this->element($controlsElement);
		} else {
			echo $this->element($controlsElement, array(), array('plugin' => $this->params['plugin']));
		}
	}

	echo $this->element('ControllerAction/index');
$this->end();
?>
