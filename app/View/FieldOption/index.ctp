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
$tableHeaders = array();
$tableHeaders[] = array($this->Label->get('general.visible') => array('class' => 'cell-visible'));
$tableHeaders[] = $this->Label->get('general.option');
if(isset($fields)) {
	foreach($fields as $field => $value) {
		if($value['display']) {
			$tableHeaders[] = __($value['label']);
		}
	}
}
$tableData = array();
if(!empty($data)) {
	foreach($data as $obj) {
		$visible = $this->Utility->checkOrCrossMarker($obj[$model]['visible']==1);
		$row = array();
		$row[] = array($visible, array('class' => 'center'));
		$linkParams = array('action' => 'view', $selectedOption, $obj[$model]['id']);
		if(isset($conditionId)) {
			$linkParams = array_merge($linkParams, array($conditionId => $selectedSubOption));
		}
		$row[] = $this->Html->link($obj[$model]['name'], $linkParams);
		if(isset($fields)) {
			foreach($fields as $field => $value) {
				if($value['display']) {
					$row[] = $obj[$model][$field];
				}
			}
		}
		$tableData[] = $row;
	}
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

$this->end();
?>
