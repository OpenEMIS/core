<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Questions'));
$this->start('contentActions');
if ($_edit) {
    echo $this->Html->link(__('Back'), array('action' => 'messagesView', $id), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>
	
<?php 
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'messagesEdit'), 'file');
echo $this->Form->create('SmsMessage', $formOptions);
?>
<?php echo $this->Form->input('original_order', array('type'=>'hidden', 'default'=>$this->request->data['SmsMessage']['order'])); ?>
<?php echo $this->Form->input('SmsMessage.id');?>
<?php echo $this->Form->input('message', array('type'=>'textarea')); ?>
<?php echo $this->Form->input('enabled', array('options'=>array('1'=>__('Yes'), '0'=>__('No')), 'default'=>$this->request->data['SmsMessage']['enabled'])); ?>
<?php echo $this->Form->input('order', array('options'=>$orderOptions)); ?>

<div class="controls">
	<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'messages'), array('class' => 'btn_cancel btn_left')); ?>
</div>
	
<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>  

