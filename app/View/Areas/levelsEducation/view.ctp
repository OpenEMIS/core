<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'levelsEducation'), array('class' => 'divider'));
if ($_edit) {
    echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'levelsEducationEdit', $data[$model]['id']), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
$this->end();
?>
