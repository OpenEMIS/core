<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');

echo $this->Html->link($this->Label->get('general.list'), array('action' => 'trainingNeed' ), array('class' => 'divider'));
if($_edit) {
	if($data[$model]['training_status_id'] == 1){
		echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'trainingNeedEdit',$id ), array('class' => 'divider'));
	}
}
if($_delete) {
	if($data[$model]['training_status_id'] == 1){
		echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'trainingNeedDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
	}
}
if($_execute) {
	if($data[$model]['training_status_id']==3){
		echo $this->Html->link($this->Label->get('StaffTrainingNeed.inactivate'), array('action' => 'trainingNeedInactivate'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmInactivate(this)'));
	}
}

$this->end();

$this->start('contentBody');
?>
<div class="row">
	<div class="col-md-3"><?php echo __('Course Title'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingCourse']['title'];?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Course Code'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingCourse']['code']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Course Description'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingCourse']['description']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Training Requirement'); ?></div>
	<div class="col-md-6"><?php echo $trainingRequirementOptions[$data['TrainingCourse']['training_requirement_id']]; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Priority'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingPriority']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Comments'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['comments']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Status'); ?></div>
	<div class="col-md-6"><?php echo (isset($workflowStatus)?  $workflowStatus : $this->TrainingUtility->getTrainingStatus($model,$data[$model]['id'],$data['TrainingStatus']['name'],$data['TrainingStatus']['id'])); ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Modified by'); ?></div>
	<div class="col-md-6"><?php echo trim($data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name']); ?></div>
</div>

<div class="row">
	<div class="col-md-3"><?php echo __('Modified on'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['modified']; ?></div>
</div>

<div class="row">
	<div class="col-md-3"><?php echo __('Created by'); ?></div>
	<div class="col-md-6"><?php echo trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']); ?></div>
</div>

<div class="row">
	<div class="col-md-3"><?php echo __('Created on'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['created']; ?></div>
</div>
<?php
echo $this->element('Training.workflow');
$this->end();
?>
