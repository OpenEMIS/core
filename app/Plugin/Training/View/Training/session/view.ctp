<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Sessions'));
$obj = $data[$modelName];
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.list'), array('action' => 'session'), array('class' => 'divider'));
if($_edit) {
	if($sessionEditable){
	    echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'sessionEdit', $obj['id']), array('class' => 'divider'));
	}
}
if($_delete) {
	if($obj['training_status_id'] == 1){
    	echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'sessionDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
	}
}
if($_execute) {
	if($obj['training_status_id']==3){
		echo $this->Html->link($this->Label->get('general.inactivate'), array('action' => 'sessionInactivate'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmInactivate(this)'));
	}
}

$this->end();

$this->start('contentBody'); ?>
<?php echo $this->element('alert'); ?>

<div class="row">
	<div class="col-md-3"><?php echo __('Course Code'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingCourse']['code']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Course Title'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingCourse']['title']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Provider'); ?></div>
	<div class="col-md-6"><?php echo $data['TrainingProvider']['name'];?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Start Date'); ?></div>
	<div class="col-md-6"><?php echo $obj['start_date'];?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('End Date'); ?></div>
	<div class="col-md-6"><?php echo $obj['end_date']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Status'); ?></div>
	<div class="col-md-6"><?php echo (isset($workflowStatus)?  $workflowStatus : $this->TrainingUtility->getTrainingStatus($model,$obj['id'],$data['TrainingStatus']['name'],$data['TrainingStatus']['id'])); ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Location'); ?></div>
	<div class="col-md-6"><?php echo $obj['location']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Comments'); ?></div>
	<div class="col-md-6"><?php echo $obj['comments']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Trainer'); ?></div>
	<div class="col-md-6"><?php echo $obj['trainer']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Trainees'); ?></div>
	<div class="col-md-6">
		<?php 
		if (!empty($trainingSessionTrainees)){ 
			foreach($trainingSessionTrainees as $val){
				echo $val['TrainingSessionTrainee']['identification_first_name'] . ', ' . $val['TrainingSessionTrainee']['identification_last_name'] . '<br />';
			}
		}else{
			echo "-";
		} ?>
	</div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo __('Modified by'); ?></div>
    <div class="col-md-6"><?php echo trim($data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name']); ?></div>
</div>

<div class="row">
    <div class="col-md-3"><?php echo __('Modified on'); ?></div>
    <div class="col-md-6"><?php echo $obj['modified']; ?></div>
</div>

<div class="row">
    <div class="col-md-3"><?php echo __('Created by'); ?></div>
    <div class="col-md-6"><?php echo trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']); ?></div>
</div>

<div class="row">
    <div class="col-md-3"><?php echo __('Created on'); ?></div>
    <div class="col-md-6"><?php echo $obj['created']; ?></div>
</div>
<?php echo $this->element('workflow');?>

<?php $this->end(); ?>
