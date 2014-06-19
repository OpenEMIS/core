<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link(__('Add'), array('action' => 'languagesAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Date'), __('Language'), __('Listening'), __('Speaking'), __('Reading'), __('Writing'));
$tableData = array();
foreach($data as $obj) {
	$row = array();
	$row[] = $obj[$model]['evaluation_date'] ;
	$row[] = $this->Html->link($obj['Language']['name'], array('action' => 'languagesView', $obj[$model]['id']), array('escape' => false)) ;
	$row[] = $obj[$model]['listening'];
	$row[] = $obj[$model]['speaking'];
	$row[] = $obj[$model]['reading'];
	$row[] = $obj[$model]['writing'];
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end();
?>
