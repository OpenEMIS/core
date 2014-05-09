<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'positionsAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Number'), __('Title'), __('Grade'), __('Teaching'), __('Status'));
$tableData = array();


foreach ($data as $obj) {
	$symbol = $this->Utility->checkOrCrossMarker($obj[$model]['type'] == 1);
	$row = array();
	$row[] = $this->Html->link($obj[$model]['position_no'], array('action' => 'positionsView', $obj[$model]['id']), array('escape' => false));
	$row[] = $obj['PositionTitle']['name'];
	$row[] = $obj['PositionGrade']['name'];
	$row[] = array($symbol, array('class' => 'center'));
	$row[] = $obj[$model]['status'];
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end();
?>