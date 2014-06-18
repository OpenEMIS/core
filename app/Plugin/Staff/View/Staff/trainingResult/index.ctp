<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentBody');
$tableHeaders = array(__('Code'), __('Title'), __('Credit'), __('Status'));
$tableData = array();
foreach ($data as $obj) {
	$row = array();
	$row[] = $obj['TrainingCourse']['code'];
	$row[] = $this->Html->link($obj['TrainingCourse']['title'], array('action' => 'trainingResultView', $obj[$modelName]['id']), array('escape' => false));
	$row[] = $obj['TrainingCourse']['credit_hours'];
	$row[] = $this->TrainingUtility->getTrainingStatus('TrainingSessionResult', $obj['TrainingSessionResult']['id'], $obj['TrainingStatus']['name'], $obj['TrainingStatus']['id']);
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

$this->end();
?>