<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));

$this->start('contentActions');
	if ($_add) {
		echo $this->Html->link($this->Label->get('general.add'), array('action' => $model, 'add'), array('class' => 'divider'));
	}
	if ($_execute) {
		echo $this->Html->link($this->Label->get('general.export'), array('action' => $model, 'export'), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
	$tableHeaders = array(
		__('Number'),
		__('Title'),
		$this->Label->get("$model.staff_position_grade_id"),
		__('Teaching'),
		$this->Label->get('general.status')
	);
	$tableData = array();
	
	foreach ($data as $obj) {
		$symbol = $this->Utility->checkOrCrossMarker($obj[$model]['type'] == 1);
		$row = array();
		$row[] = $this->Html->link($obj[$model]['position_no'], array('action' => $model, 'view', $obj[$model]['id']), array('escape' => false));
		$row[] = $obj['StaffPositionTitle']['name'];
		$row[] = $obj['StaffPositionGrade']['name'];
		$row[] = array($symbol, array('class' => 'center'));
		$row[] = $fields['status']['options'][$obj[$model]['status']];
		$tableData[] = $row;
	}
	echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end();
?>
