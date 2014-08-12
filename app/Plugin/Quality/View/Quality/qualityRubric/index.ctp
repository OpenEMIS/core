<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if($_add) {
    echo $this->Html->link(__('Add'), array('action' => 'qualityRubricAdd'), array('class' => 'divider', 'id'=>'add'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Year'), __('Name'), __('Class'), __('Staff'));
$tableData = array();
foreach ($data as $obj) {
	$staffName = $obj['Staff']['first_name'] . ' ' . $obj['Staff']['last_name'];
	$row = array();
	$row[] = $obj['SchoolYear']['name'];
	$row[] = $this->Html->link($obj['RubricsTemplate']['name'], array('action' => 'qualityRubricView', $obj[$model]['id']), array('escape' => false));
	$row[] = $obj['InstitutionSiteClass']['name'];
	$row[] = $staffName;

	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

$this->end(); ?>  