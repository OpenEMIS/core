<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model.title")));

$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model), array('class' => 'divider'));
	if ($_edit) {
		echo $this->Html->link($this->Label->get('general.edit'), array('action' => $model, 'edit', $data[$model]['id']), array('class' => 'divider'));
	}
	if ($_delete) {
		echo $this->Html->link($this->Label->get('general.delete'), array('action' => $model, 'remove'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
	}
$this->end();

$this->start('contentBody');
	echo $this->element('view');
$this->end();
?>
