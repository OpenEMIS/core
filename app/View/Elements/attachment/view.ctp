<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', 'Attachment Details');

$this->start('contentActions');
echo $this->Html->link(__('List'), array('action' => 'attachments'), array('class' => 'divider'));
if($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'attachmentsEdit', $data[$model]['id']), array('class' => 'divider'));
}
if($_delete) {
	echo $this->Html->link(__('Delete'), array('action' => 'attachmentsDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
$this->end();
?>
