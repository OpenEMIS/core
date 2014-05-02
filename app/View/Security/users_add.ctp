<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Add User'));
$this->start('contentActions');

$this->end();
$this->assign('contentId', 'users');
$this->assign('contentClass', 'edit details');

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<?php
$formOptions = $this->FormUtility->getFormOptions(array('controller' =>'Security','action' => 'usersAdd'));
echo $this->Form->create('SecurityUser', $formOptions);
?>

<fieldset class="section_break">
	<legend><?php echo __('Login'); ?></legend>
	<?php echo $this->Form->input('username'); ?>
	<?php echo $this->Form->input('password'); ?>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Information'); ?></legend>
	<?php echo $this->Form->input('identification_no'); ?>
	<?php echo $this->Form->input('first_name'); ?>
	<?php echo $this->Form->input('last_name'); ?>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Contact'); ?></legend>
	<?php echo $this->Form->input('telephone'); ?>
	<?php echo $this->Form->input('email'); ?>
</fieldset>

<div class="controls">
	<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'users'), array('class' => 'btn_cancel btn_left')); ?>
</div>
<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>