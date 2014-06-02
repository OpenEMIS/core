<?php /*echo $this->element('breadcrumb'); ?>
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
 * 
 */?>

<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentId', 'users');
$this->assign('contentHeader', $header);
$this->start('contentBody');
if ($allowChangePassword) {
	$formOptions = $this->FormUtility->getFormOptions(array('controller' => 'Home', 'action' => 'password'));
	echo $this->Form->create('SecurityUser', $formOptions);
	echo $this->Form->input('oldPassword', array('id' => 'oldPassword', 'type' => 'password', 'label'=>array('text'=> $this->Label->get('password.oldPassword'),'class'=>'col-md-3 control-label')));
	echo $this->Form->input('newPassword', array('id' => 'passwordInput', 'type' => 'password'));
	echo $this->Form->input('retypePassword', array('id' => 'retypePasswordIxnput', 'type' => 'password', 'label'=>array('text'=> $this->Label->get('password.retypePassword'),'class'=>'col-md-3 control-label')));
	?>
	<div class="form-group">
		<div class="col-md-offset-4">
			<input id="update" type="submit" value="<?php echo __('Update'); ?>" class="btn_save" />
		</div>
	</div>
	<?php
}
$this->end();
?>