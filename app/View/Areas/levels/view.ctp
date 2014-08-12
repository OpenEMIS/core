<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'levels'), array('class' => 'divider'));
if ($_edit) {
    echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'levelsEdit', $data[$model]['id']), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
$this->end();
?>
