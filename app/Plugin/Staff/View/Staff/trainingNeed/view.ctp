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
	<div class="col-md-3"><?php echo __('Need Type'); ?></div>
	<div class="col-md-6"><?php echo $trainingNeedTypes[$data[$model]['ref_course_table']];?></div>
</div>
<?php if($data[$model]['ref_course_table']=='TrainingNeedCategory'){ ?>
<div class="row">
	<div class="col-md-3"><?php echo __('Need Category'); ?></div>
	<div class="col-md-6"><?php echo $trainingNeedCategoryOptions[$data[$model]['ref_course_id']];?></div>
</div>
<?php } ?>
<div class="row">
	<div class="col-md-3"><?php echo __('Course Title'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['ref_course_title'];?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Course Code'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['ref_course_code']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Course Description'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['ref_course_description']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Training Requirement'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['ref_course_requirement']; ?></div>
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
