<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentBody');
if(!empty($data)) { 
	$tableHeaders = array($this->Label->get('general.date'), $this->Label->get('general.category'), $this->Label->get('general.title'), $this->Label->get('InstitutionSite.name'));
	$tableData = array();
	foreach ($data as $obj) {
		$row = array();
		$row[] = array($obj[$model]['date_of_behaviour'], array('class'=>array('center')));
		$row[] = array($obj['StaffBehaviourCategory']['name'], array('class'=>array('center')));
		$row[] = $this->Html->link($obj[$model]['title'], array('action' => 'behaviourView', $obj[$model]['id']), array('escape' => false));
		$row[] = $obj['InstitutionSite']['name'];
		$tableData[] = $row;
	}
	echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
}
$this->end();
?>