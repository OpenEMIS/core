<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');

echo $this->Html->link($this->Label->get('general.back'), array('action' => 'status'), array('class' => 'divider'));
if ($_edit) {
    echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'statusEdit', $id), array('class' => 'divider'));
}

if ($_delete && !$disableDelete) {
    echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'statusDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();
$this->start('contentBody'); 
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
$this->end(); ?>
