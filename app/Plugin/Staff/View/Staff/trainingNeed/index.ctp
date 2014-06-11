<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'trainingNeedAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Code'), __('Title'), __('Credit'), __('Status'));
$tableData = array();
foreach ($data as $obj) {
	$row = array();
	$row[] = $obj['StaffTrainingNeed']['ref_course_code'];
	$row[] = $this->Html->link($obj['StaffTrainingNeed']['ref_course_title'] , array('action' => 'trainingNeedView', $obj[$model]['id']), array('escape' => false));
	$row[] = $obj['TrainingCourse']['credit_hours'];
	$status = $this->TrainingUtility->getTrainingStatus($model, $obj[$model]['id'], $obj['TrainingStatus']['name'], $obj[$model]['training_status_id']);
	$row[] = $status;
	
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

$this->end();
?>