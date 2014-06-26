<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'extracurricularAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Year'), __('Start Date'), __('Type'), __('Title'));
$tableData = array();

foreach($data as $obj) {
	$row = array();
	$row[] = $obj['SchoolYear']['name'];
	$row[] = $obj['StaffExtracurricular']['start_date'] ;
	$row[] = $obj['ExtracurricularType']['name'] ;
	$row[] = $this->Html->link($obj[$model]['name'], array('action' => 'extracurricularView', $obj[$model]['id']), array('escape' => false));
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end(); 
?>
