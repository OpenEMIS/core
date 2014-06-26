<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'healthImmunizationAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Date'), __('Immunization'), __('Dosage'), __('Comment'));
$tableData = array();


foreach ($data as $obj) {
	$row = array();
	$row[] = $obj[$model]['date'];
	$row[] = $this->Html->link($obj['HealthImmunization']['name'], array('action' => 'healthImmunizationView', $obj[$model]['id']), array('escape' => false));
	$row[] = $obj[$model]['dosage'];
	$row[] = $obj[$model]['comment'];
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end();
?>