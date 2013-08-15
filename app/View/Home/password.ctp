<?php echo $this->element('breadcrumb'); ?>
<style type="text/css">
.alert.alert_error{
	top: 0px !important;
}
</style>

<div id="password" class="content_wrapper">
	<h1><?php echo __('Change Password');?></h1>
	<?php echo $this->element('alert'); ?>
	
	<?php if($allowChangePassword) { ?>
	<?php
	echo $this->Form->create('SecurityUser', array(
		'url' => array('controller' => 'Home', 'action' => 'password'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
	<div class="row input" style="margin-left: 10px;">
		<div class="label"><?php echo __('Current Password'); ?></div>
		<div class="value"><?php echo $this->Form->input('oldPassword', array('id' => 'oldPassword', 'type' => 'password')); ?></div>
	</div>
	<div class="row input" style="margin-left: 10px;">
		<div class="label"><?php echo __('New Password'); ?></div>
		<div class="value"><?php echo $this->Form->input('newPassword', array('id' => 'passwordInput', 'type' => 'password')); ?></div>
	</div>
	<div class="row input" style="margin-left: 10px;">
		<div class="label"><?php echo __('Retype New Password'); ?></div>
		<div class="value"><?php echo $this->Form->input('retypePassword', array('id' => 'retypePasswordIxnput', 'type' => 'password')); ?></div>
	</div>
	
	<div class="controls view_controls">
		<input id="update" type="submit" value="<?php echo __('Update'); ?>" class="btn_save" />
	</div>
	<?php echo $this->Form->end(); ?>
	<?php } // end if ?>
</div>
