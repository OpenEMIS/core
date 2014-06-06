<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $data[$model]['name']);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => $_action, $data[$model]['school_year_id']), array('class' => 'divider'));
if ($_edit) {
    echo $this->Html->link($this->Label->get('general.edit'), array('action' => $_action.'Edit', $data[$model]['id']), array('class' => 'divider'));
}
if ($_delete) {
	echo $this->Html->link($this->Label->get('general.delete'), array('action' => $_action.'Delete', $data[$model]['id']), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->start('contentBody');
echo $this->element('../InstitutionSites/classes/controls');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
$this->end();
?>
