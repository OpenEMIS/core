<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model.title")));

$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), $_buttons['back']['url'], array('class' => 'divider'));
	if ((isset($_edit) && $_edit) || !isset($_edit)) {
		echo $this->Html->link($this->Label->get('general.edit'), $_buttons['edit']['url'], array('class' => 'divider'));
	}
	if ((isset($_delete) && $_delete) || !isset($_delete)) {
		echo $this->Html->link($this->Label->get('general.delete'), $_buttons['remove']['url'], array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
	}
$this->end();

$this->start('contentBody');
	echo $this->element('ControllerAction/view');
$this->end();
?>
