<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentBody');
if(!empty($data)) { 
	$tableHeaders = array(__('Date'), __('Category'), __('Title'), __('Insitution Site'));
	$tableData = array();
	foreach ($data as $obj) {
		$row = array();
		$row[] = $obj[$model]['date_of_behaviour'];
		$row[] = $obj['StudentBehaviourCategory']['name'];
		$row[] = $this->Html->link($obj[$model]['title'], array('action' => 'behaviourView', $obj[$model]['id']), array('escape' => false));
		$row[] = $obj['InstitutionSite']['name'];
		$tableData[] = $row;
	}
	echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
}
$this->end();
?>