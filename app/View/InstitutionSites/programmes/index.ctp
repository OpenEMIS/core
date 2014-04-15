<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Programmes'));
$this->start('contentActions');
if($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'programmesEdit', $selectedYear), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('templates/year_options', array('url' => 'programmes'));
$tableHeaders = array(__('Programme'), __('System'), __('Cycle'));
$tableData = array();
foreach($data as $obj) {
	$row = array();
	$row[] = $obj['education_programme_name'];
	$row[] = $obj['education_system_name'];
	$row[] = $obj['education_cycle_name'];
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end();
?>
