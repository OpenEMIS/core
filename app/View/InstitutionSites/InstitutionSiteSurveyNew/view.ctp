<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));
$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'index'), array('class' => 'divider'));
	if ($_add) {
	    echo $this->Html->link(__('Start'), array('action' => $model, 'add'), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
?>

	<div class="row">
		<div class="col-md-3"><?php echo __('Name'); ?></div>
		<div class="col-md-6"><?php echo $data['SurveyStatus']['SurveyTemplate']['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Academic Period Type'); ?></div>
		<div class="col-md-6"><?php echo $data['SurveyStatus']['AcademicPeriodType']['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Academic Period'); ?></div>
		<div class="col-md-6"><?php echo $data['AcademicPeriod']['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Modified by'); ?></div>
		<div class="col-md-6"><?php if(isset($data['ModifiedUser'])) { echo $data['ModifiedUser']['first_name'] . " " . $data['ModifiedUser']['last_name']; } ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Modified on'); ?></div>
		<div class="col-md-6"><?php if(isset($data[$model])) { echo $data[$model]['modified']; } ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Created by'); ?></div>
		<div class="col-md-6"><?php if(isset($data['CreatedUser'])) { echo $data['CreatedUser']['first_name'] . " " . $data['ModifiedUser']['last_name']; } ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Created on'); ?></div>
		<div class="col-md-6"><?php if(isset($data[$model])) { echo $data[$model]['created']; } ?></div>
	</div>

<?php $this->end(); ?>