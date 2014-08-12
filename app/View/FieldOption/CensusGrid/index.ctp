<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
if($_add) {
	$params = array('action' => 'add', $selectedOption);
	echo $this->Html->link($this->Label->get('general.add'), $params, array('class' => 'divider'));
}
if($_edit && count($data) > 1) {
	$params = array('action' => 'indexEdit', $selectedOption);
	echo $this->Html->link($this->Label->get('general.reorder'), $params, array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');

echo $this->element('../FieldOption/controls');
$tableHeaders = array();
$tableHeaders[] = array($this->Label->get('general.visible') => array('class' => 'cell-visible'));
$tableHeaders[] = $this->Label->get('general.name');
$tableHeaders[] = $this->Label->get('general.description');
$tableHeaders[] = $this->Label->get('InstitutionSite.institution_site_type_id');

$tableData = array();
foreach($data as $obj) {
	$visible = $this->Utility->checkOrCrossMarker($obj[$model]['visible']==1);
	$name = $obj[$model]['name'];
	$linkParams = array('action' => 'view', $selectedOption, $obj[$model]['id']);
	$row = array();
	$row[] = array($visible, array('class' => 'center'));
	$row[] = $this->Html->link($name, $linkParams);
	$row[] = $obj[$model]['description'];
	$row[] = !empty($obj['InstitutionSiteType']['name']) ? $obj['InstitutionSiteType']['name'] : __('All');
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

$this->end();
?>
