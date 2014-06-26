<?php

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.list'), array('action' => 'positions'), array('class' => 'divider'));
if ($_edit) {
    echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'positionsEdit', $data[$model]['id']), array('class' => 'divider'));
}
if ($_delete) {
    echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'positionsDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}

echo $this->Html->link($this->Label->get('general.history'), array('action' => 'positionsHistory', $data[$model]['id']), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
$this->end();
?>
