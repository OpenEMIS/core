<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('bankaccounts', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="bankAccountAdd" class="content_wrapper add">
    <h1>
        <span><?php echo __('Add Message'); ?></span>
    </h1>
    <?php echo $this->element('alert'); ?>
	
	<?php 
	echo $this->Form->create('SmsMessage', array(
			'url' => array('controller' => 'Sms', 'action' => 'messagesAdd'),
			'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
		));
	?>
	<div class="messageForm">

	<?php echo $this->Form->input('original_order', array('type'=>'hidden', 'default'=>$defaultOrder)); ?>
	<div class="row edit">
        <div class="label"><?php echo __('Message'); ?></div>
        <div class="value"><?php echo $this->Form->input('message', array('type'=>'textarea', 'maxlength'=>"160")); ?></div>
    </div>
    
    <div class="row edit">
		<div class="label"><?php echo __('Enabled'); ?></div>
		<div class="value"><?php echo $this->Form->input('enabled', array('options'=>array('1'=>__('Yes'), '0'=>__('No')), 'default'=>1)); ?></div>
	</div>
	 <div class="row">
        <div class="label"><?php echo __('Order'); ?></div>
         <div class="value"><?php echo $this->Form->input('order', array('options'=>$orderOptions, 'default'=>$defaultOrder)); ?></div>
    </div>

	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'messages'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
	</div>
</div>
