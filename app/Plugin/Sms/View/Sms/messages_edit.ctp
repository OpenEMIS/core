<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="bankAccountEdit" class="content_wrapper add">
   <h1>
        <span><?php echo __('Messages'); ?></span>
        <?php 
        if ($_edit) {
            echo $this->Html->link(__('Back'), array('action' => 'messagesView', $id), array('class' => 'divider'));
        }
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
	<div class="messageForm">
	<?php 
	echo $this->Form->create('SmsMessage', array(
			'url' => array('controller' => 'Sms', 'action' => 'messagesEdit'),
			'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
		));
	?>
	<?php echo $this->Form->input('original_order', array('type'=>'hidden', 'default'=>$this->request->data['SmsMessage']['order'])); ?>
	<?php echo $this->Form->input('SmsMessage.id');?>
	<div class="row edit">
        <div class="label"><?php echo __('Message'); ?></div>
        <div class="value"><?php echo $this->Form->input('message', array('type'=>'textarea', 'maxlength'=>"160")); ?></div>
    </div>
    
    <div class="row edit">
		<div class="label"><?php echo __('Enabled'); ?></div>
		<div class="value"><?php echo $this->Form->input('enabled', array('options'=>array('1'=>__('Yes'), '0'=>__('No')), 'default'=>$this->request->data['SmsMessage']['enabled'])); ?></div>
	</div>

	 <div class="row">
        <div class="label"><?php echo __('Order'); ?></div>
         <div class="value"><?php echo $this->Form->input('order', array('options'=>$orderOptions)); ?></div>
    </div>

	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'messages'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
	</div>
</div>
