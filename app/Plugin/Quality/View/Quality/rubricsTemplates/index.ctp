<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('table_cell', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'rubricsTemplatesAdd'), array('class' => 'divider', 'id' => 'add'));
}
$this->end();

$this->start('contentBody');

$tableHeaders = array(__('Name'), __('Description'), __('Action'));
$tableData = array();
foreach ($data as $obj) {
	$row = array();
	$row[] = $this->Html->link('<div>' . $obj[$modelName]['name'] . '</div>', array('action' => 'rubricsTemplatesHeader', $obj[$modelName]['id']), array('escape' => false));
	$row[] = $obj[$modelName]['description'];
	$row[] = array($this->Html->link('<div>' . __('View Details') . '</div>', array('action' => 'rubricsTemplatesView', $obj[$modelName]['id']), array('escape' => false)), array('class'=>'cell-action'));
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end();
?>  