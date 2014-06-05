<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
if($_add) {
	$params = array('action' => 'add', $selectedOption);
	if(isset($conditionId)) {
		$params = array_merge($params, array($conditionId => $selectedSubOption));
	}
	echo $this->Html->link($this->Label->get('general.add'), $params, array('class' => 'divider'));
}
if($_edit && count($data) > 1) {
	$params = array('action' => 'indexEdit', $selectedOption);
	if(isset($conditionId)) {
		$params = array_merge($params, array($conditionId => $selectedSubOption));
	}
	echo $this->Html->link($this->Label->get('general.reorder'), $params, array('class' => 'divider'));
}
$this->end(); // end contentActions

$this->start('contentBody');

echo $this->element('../FieldOption/controls');
$hasDefault = false;
if(!empty($data)) {
	$first = current($data);
	$hasDefault = isset($first[$model]['default']);
}
$tableHeaders = array();
$tableHeaders[] = array($this->Label->get('general.visible') => array('class' => 'cell-visible'));
if($hasDefault) {
	$tableHeaders[] = array($this->Label->get('general.default') => array('class' => 'cell-visible'));
}
$tableHeaders[] = $this->Label->get('general.option');
if(isset($fields)) {
	foreach($fields['fields'] as $value) {
		if(isset($value['display']) && $value['display']) {
			$tableHeaders[] = __(ucfirst($value['field']));
		}
	}
}
$tableData = array();
foreach($data as $obj) {
	$visible = $this->Utility->checkOrCrossMarker($obj[$model]['visible']==1);
	$name = isset($obj[$model]['name']) ? $obj[$model]['name'] : $obj[$model]['value'];
	$row = array();
	$row[] = array($visible, array('class' => 'center'));
	$linkParams = array('action' => 'view', $selectedOption, $obj[$model]['id']);
	if(isset($conditionId)) {
		$linkParams = array_merge($linkParams, array($conditionId => $selectedSubOption));
	}
	if($hasDefault) {
		$default = $this->Utility->checkOrCrossMarker($obj[$model]['default']==1);
		$row[] = array($default, array('class' => 'center'));
	}
	$row[] = $this->Html->link($name, $linkParams);
	if(isset($fields)) {
		foreach($fields['fields'] as $value) {
			if(isset($value['display']) && $value['display']) {
				if($value['type'] != 'select') {
					$row[] = $obj[$model][$value['field']];
				} else {
					$row[] = $value['options'][$obj[$model][$value['field']]];
				}
			}
		}
	}
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

$this->end();
?>
