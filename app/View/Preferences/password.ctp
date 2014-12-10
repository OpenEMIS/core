<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentBody');
if ($allowChangePassword) {
	$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'password'));
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
