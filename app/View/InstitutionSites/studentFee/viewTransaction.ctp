<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.list'), array('action' => 'studentFeeView', $studentId, $feeId), array('class' => 'divider', 'id'=>'back'));

if($_edit) {
    echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'studentFeeEditTransaction', $id), array('class' => 'divider', 'id'=>'edit'));
}
if($_delete) {
    echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'studentFeeDeleteTransaction'), array('class' => 'divider', 'id'=>'delete'));
}
$this->end();

$this->start('contentBody'); 
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
$this->end();
?>