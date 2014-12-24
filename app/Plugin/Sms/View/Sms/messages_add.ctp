<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('bankaccounts', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Questions'));
$this->start('contentActions');
if($_add) {
    echo $this->Html->link(__('Back'), array('action' => 'messages'), array('class' => 'divider', 'id'=>'add'));
}
$this->end();

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>
<?php 
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'messagesAdd'), 'file');
echo $this->Form->create('SmsMessage', $formOptions);
?>
<?php echo $this->Form->input('original_order', array('type'=>'hidden', 'default'=>$defaultOrder)); ?>
<?php echo $this->Form->input('message', array('type'=>'textarea', 'maxlength'=>"160")); ?>
<?php echo $this->Form->input('enabled', array('options'=>array('1'=>__('Yes'), '0'=>__('No')), 'default'=>1)); ?>
<?php echo $this->Form->input('order', array('options'=>$orderOptions, 'default'=>$defaultOrder)); ?>

<div class="controls">
	<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'messages'), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>  
