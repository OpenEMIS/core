<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'trainingSelfStudyAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('End Date'), __('Title'), __('Credit'), __('Status'));
$tableData = array();
foreach ($data as $obj) {
	$row = array();
	$row[] = $obj[$model]['end_date'];
	$row[] = $this->Html->link($obj[$model]['title'], array('action' => 'trainingSelfStudyView', $obj[$model]['id']), array('escape' => false));
	$row[] = $obj[$model]['credit_hours'];
	if(isset($obj['StaffTrainingSelfStudyResult']) && $obj['StaffTrainingSelfStudyResult']['training_status_id']=='2'){
		$row[] = (isset($workflowStatus)?  $workflowStatus : $this->TrainingUtility->getTrainingStatus($model, $obj[$model]['id'], $obj['TrainingStatus']['name'], $obj['StaffTrainingSelfStudyResult']['training_status_id']));
	}else{
		$row[] = (isset($workflowStatus)?  $workflowStatus : $this->TrainingUtility->getTrainingStatus($model, $obj[$model]['id'], $obj['TrainingStatus']['name'], $obj[$model]['training_status_id']));
	}
	
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

$this->end();
?>