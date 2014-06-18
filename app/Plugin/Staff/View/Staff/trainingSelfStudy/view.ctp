<?php
echo $this->Html->script('search', false);
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$obj = $data[$model]; 
$this->start('contentActions');


echo $this->Html->link($this->Label->get('general.list'), array('action' => 'trainingSelfStudy' ), array('class' => 'divider'));
if($_edit) {
	if($obj['training_status_id'] == 1 || $resultEditable){
		echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'trainingSelfStudyEdit',$obj['id'] ), array('class' => 'divider'));
	}
}
if($_delete) {
	if($obj['training_status_id'] == 1){
		echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'trainingSelfStudyDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
	}
}
$this->end();

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
?>
<div class="row">
	<div class="col-md-3"><?php echo __('Status'); ?></div>
	<div class="col-md-6"><?php echo (isset($workflowStatus)?  $workflowStatus : $this->TrainingUtility->getTrainingStatus($model,$obj['id'],$data['TrainingStatus']['name'],$data['TrainingStatus']['id'])); ?></div>
</div>

<?php 
echo $this->element('layout/view', array('fields' => $fields2, 'data' => $data));
echo $this->element('Training.workflow');
$this->end();
?>
