<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'healthConsultationAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Date'), __('Type'), __('Description'), __('Treatment'));
$tableData = array();
foreach ($data as $obj) {
	$row = array();
	$row[] = $obj[$model]['date'];
	$row[] = $this->Html->link($obj['HealthConsultationType']['name'], array('action' => 'healthConsultationView', $obj[$model]['id']), array('escape' => false));
	$row[] = $obj[$model]['description'];
	$row[] = $obj[$model]['treatment'];
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

$this->end();
?>