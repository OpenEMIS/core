<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
echo $this->Html->link(__('List'), array('action' => 'comments'), array('class' => 'divider'));
if($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'commentsEdit', $data[$model]['id']), array('class' => 'divider'));
}
if($_delete) {
	echo $this->Html->link(__('Delete'), array('action' => 'commentsDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
$this->end();
?>
