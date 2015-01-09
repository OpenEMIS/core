<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	echo $this->Html->link(__('Back'), array('action' => 'index'), array('class' => 'divider'));
	if ($_edit) {
		echo $this->Html->link(__('Edit'), array('action' => 'edit', $data['SurveyTemplate']['id']), array('class' => 'divider'));
	}
	if ($_delete) {
		echo $this->Html->link(__('Delete'), array('action' => 'delete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
	}
	echo $this->Html->link(__('Details'), array('controller' => 'SurveyQuestions', 'action' => 'index'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>

	<div class="row">
		<div class="col-md-3"><?php echo __('Name'); ?></div>
		<div class="col-md-6"><?php echo $data['SurveyTemplate']['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Module'); ?></div>
		<div class="col-md-6"><?php echo $data['SurveyModule']['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Description'); ?></div>
		<div class="col-md-6"><?php echo $data['SurveyTemplate']['description']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Modified by'); ?></div>
		<div class="col-md-6"><?php echo $data['ModifiedUser']['first_name'] . " " . $data['ModifiedUser']['last_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Modified on'); ?></div>
		<div class="col-md-6"><?php echo $data['SurveyTemplate']['modified']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Created by'); ?></div>
		<div class="col-md-6"><?php echo $data['CreatedUser']['first_name'] . " " . $data['ModifiedUser']['last_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Created on'); ?></div>
		<div class="col-md-6"><?php echo $data['SurveyTemplate']['created']; ?></div>
	</div>

<?php $this->end(); ?>