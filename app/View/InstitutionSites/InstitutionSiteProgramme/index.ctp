<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model.title")));
$this->start('contentActions');
	if($_edit) {
		echo $this->Html->link($this->Label->get('general.edit'), array('action' => $model, 'edit', $selectedYear), array('class' => 'divider'));
	}
	if ($_execute) {
		echo $this->Html->link($this->Label->get('general.export'), array('action' => $model, 'excel'), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
echo $this->element('templates/year_options', array('url' => $model . '/index'));
$tableHeaders = array(__('Programme'), __('Cycle'));
$tableData = array();
foreach($data as $obj) {
	$row = array();
	$row[] = $obj['EducationProgramme']['name'];
	$row[] = $obj['EducationProgramme']['EducationCycle']['name'];
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end();
?>
