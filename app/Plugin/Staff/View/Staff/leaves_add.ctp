<?php echo $this->element('breadcrumb'); ?>
<div id="leaves" class="content_wrapper edit">
	<h1>
		<span><?php echo __('Leave'); ?></span>
	</h1>
	<?php echo $this->element('alert'); ?>

	<?php
	echo $this->Form->create('StaffLeave', array(
		'url' => array('controller' => 'Staff', 'action' => 'leavesAdd'),
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off')
	));
	?>
	
	<div class="row">
		<div class="label"><?php echo __('Type'); ?></div>
		<div class="value"><?php echo $this->Form->input('staff_leave_type_id', array('options' => $typeOptions, 'class' => 'default')); ?></div>
	</div>

	<div class="row">
		<div class="label"><?php echo __('From'); ?></div>
		<div class="value"><?php echo $this->Form->input('date_from', array('type' => 'date', 'dateFormat' => 'DMY', 'before' => '<div class="left">', 'after' => '</div>')); ?></div>
	</div>

	<div class="row">
		<div class="label"><?php echo __('To'); ?></div>
		<div class="value"><?php echo $this->Form->input('date_to', array('type' => 'date', 'dateFormat' => 'DMY', 'selected' => date('Y-m-d', time()+86400))); ?></div>
	</div>
	
	<div class="row">
		<div class="label"><?php echo __('Comments'); ?></div>
		<div class="value"><?php echo $this->Form->input('comments', array('type' => 'textarea')); ?></div>
	</div>
	
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'leaves'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>