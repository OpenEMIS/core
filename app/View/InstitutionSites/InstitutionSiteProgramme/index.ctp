<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model.title")));
$this->start('contentActions');
	if($_add) {
		echo $this->Html->link($this->Label->get('general.add'), array('action' => $model, 'add'), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Programme'), __('Start Date'), __('End Date'), __('Cycle'));
$tableData = array();
foreach($data as $obj) {
	$row = array();
	$row[] = $this->Html->link($obj['EducationProgramme']['name'], array('action' => $this->action, 'view', $obj['InstitutionSiteProgramme']['id']));
	$row[] = $obj['InstitutionSiteProgramme']['start_date'];
	$row[] = $obj['InstitutionSiteProgramme']['end_date'];
	$row[] = $obj['EducationProgramme']['EducationCycle']['name'];
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end();
?>
