<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.list'), array('action' => 'license'), array('class' => 'divider'));
if($_edit) {
    echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'licenseEdit', $id), array('class' => 'divider'));
}
if($_delete) {
    echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'licenseDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
$this->end();
?>
