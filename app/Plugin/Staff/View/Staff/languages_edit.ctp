<?php echo $this->element('breadcrumb'); ?>
<?php echo $this->Html->script('app.date', false); ?>

<div id="language" class="content_wrapper edit add">
     <h1>
        <span><?php echo __('Languages'); ?></span>
        <?php 
        if ($_edit) {
            echo $this->Html->link(__('Back'), array('action' => 'languagesView', $id), array('class' => 'divider'));
        }
        ?>
    </h1>
	<?php
	echo $this->Form->create('StaffLanguage', array(
		'url' => array('controller' => 'Staff', 'action' => 'languagesEdit'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
    <?php $obj = @$this->request->data['StaffLanguage']; ?>
	<?php echo $this->Form->input('StaffLanguage.id');?>
	 <div class="row">
        <div class="label"><?php echo __('Language'); ?></div>
        <div class="value"><?php echo $this->Form->input('language_id', array('empty'=>__('--Select--'),'options'=>$languageOptions, 'default'=> $obj['language_id'])); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Listening'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('listening', array('onkeypress' => 'return utility.integerCheck(event)')); ?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Speaking'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('speaking', array('onkeypress' => 'return utility.integerCheck(event)')); ?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Reading'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('reading', array('onkeypress' => 'return utility.integerCheck(event)')); ?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Writing'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('writing', array('onkeypress' => 'return utility.integerCheck(event)')); ?>
        </div>
    </div>

	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'languagesView', $id), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
