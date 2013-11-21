<?php echo $this->element('breadcrumb'); ?>
<?php echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false)); ?>
<?php echo $this->Html->script('jquery-ui.min', false); ?>

<div id="comment" class="content_wrapper edit add">
     <h1>
        <span><?php echo __('Comments'); ?></span>
        <?php 
        if ($_edit) {
            echo $this->Html->link(__('Back'), array('action' => 'commentsView', $id), array('class' => 'divider'));
        }
        ?>
    </h1>
	<?php
	echo $this->Form->create('StaffComment', array(
		'url' => array('controller' => 'Staff', 'action' => 'commentsEdit'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default'),
        'type' => 'file'
	));
	?>
	<?php echo $this->Form->input('StaffComment.id');?>
	 <div class="row">
        <div class="label"><?php echo __('Title'); ?></div>
        <div class="value"><?php echo $this->Form->input('title'); ?></div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Date'); ?></div>
        <div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'comment_date', array('desc' => true,' default' => $this->request->data['StaffComment']['comment_date'])); ?></div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Comment'); ?></div>
        <div class="value"><?php echo $this->Form->input('comment', array('type'=>'textarea')); ?></div>
    </div>

	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'qualificationsView', $id), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
