<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'index'), array('class' => 'divider'));
if($_edit) {
	echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'edit', $id), array('class' => 'divider'));
}
if($_delete) {
	echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'delete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->start('contentBody');
echo $this->element('view');
$this->end();
?>
