<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link(__('Add'), array('action' => 'nationalitiesAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Country'), __('Comments'));
$tableData = array();
foreach($data as $obj) {	
	$row = array();
		$row[] = $this->Html->link($obj['Country']['name'], array('action' => 'nationalitiesView', $obj[$model]['id']), array('escape' => false)) ;
		$row[] = $obj[$model]['comments'] ;
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end();
?>
