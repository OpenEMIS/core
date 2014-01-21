<?php echo $this->element('breadcrumb'); ?>
<?php echo $this->Html->script('app.date', false); ?>

<div id="nationality" class="content_wrapper edit add">
     <h1>
        <span><?php echo __('Nationalities'); ?></span>
        <?php 
        if ($_edit) {
            echo $this->Html->link(__('Back'), array('action' => 'nationalitiesView', $id), array('class' => 'divider'));
        }
        ?>
    </h1>
	<?php
	echo $this->Form->create('StaffNationality', array(
		'url' => array('controller' => 'Staff', 'action' => 'nationalitiesEdit'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
    <?php $obj = @$this->request->data['StaffNationality']; ?>
	<?php echo $this->Form->input('StaffNationality.id');?>
	 <div class="row">
        <div class="label"><?php echo __('Country'); ?></div>
        <div class="value"><?php echo $this->Form->input('country_id', array('empty'=>__('--Select--'),'options'=>$countryOptions, 'default'=> $obj['country_id'])); ?></div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Comments'); ?></div>
        <div class="value"><?php echo $this->Form->input('comments', array('type'=>'textarea')); ?></div>
    </div>

	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'nationalitiesView', $id), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
