<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'healthAllergyAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Type'), __('Description'), __('Severe'), __('Comment'));
$tableData = array();


foreach ($data as $obj) {
	$symbol = $this->Utility->checkOrCrossMarker($obj[$model]['severe'] == 1);
	$row = array();
	$row[] = $this->Html->link($obj['HealthAllergyType']['name'], array('action' => 'healthAllergyView', $obj[$model]['id']), array('escape' => false));
	$row[] = $obj[$model]['description'];
	$row[] = array($symbol, array('class' => 'center'));
	$row[] = $obj[$model]['comment'];
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end();
?>