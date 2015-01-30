<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));

$this->start('contentActions');
	if ($_add) {
		echo $this->Html->link($this->Label->get('general.add'), array('action' => 'qualityVisitAdd'), array('class' => 'divider'));
	}
	if ($_execute) {
		echo $this->Html->link($this->Label->get('general.export'), array('action' => 'qualityVisitExcel'), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Date'), __('Grade'), __('Class'), __('Staff'));
$tableData = array();
foreach ($data as $obj) {
	$staffName = $this->Model->getName($obj['Staff']);
	$row = array();
	$row[] = $obj[$model]['date'];
	$row[] = $obj['EducationGrade']['name'];
	$row[] = $this->Html->link($obj['InstitutionSiteClass']['name'], array('controller' => $this->params['controller'], 'action' => 'qualityVisitView', $obj[$model]['id']), array('escape' => false));
	$row[] = $staffName;

	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end();
?>