<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));

$this->start('contentActions');
echo $this->Html->link(__('List'), array('action' => 'studentFeeView', $studentId, $feeId), array('class' => 'divider', 'id'=>'back'));

if($_edit) {
    echo $this->Html->link(__('Edit'), array('action' => 'studentFeeEditTransaction', $id), array('class' => 'divider', 'id'=>'edit'));
}
if($_delete) {
    echo $this->Html->link(__('Delete'), array('action' => 'studentFeeDeleteTransaction'), array('class' => 'divider', 'id'=>'delete'));
}
$this->end();

$this->start('contentBody'); 
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
$this->end();
?>