<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
        if($_edit) {
            echo $this->Html->link(__('Edit'), array('action' => 'healthEdit'), array('class' => 'divider'));
        }
$this->end();

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
$this->end();
?>

