<?php echo $this->element('breadcrumb'); ?>
<div id="leaves" class="content_wrapper">
	<h1>
		<span><?php echo __('Leave'); ?></span>
		<?php
		echo $this->Html->link(__('Back'), array('action' => 'leaves'), array('class' => 'divider'));
		if ($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'leavesEdit', $data['TeacherLeave']['id']), array('class' => 'divider'));
		}
		if($_delete) {
			echo $this->Html->link(__('Delete'), array('action' => 'leavesDelete', $data['TeacherLeave']['id']), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>

	<div class="row">
		<div class="label"><?php echo __('Type'); ?></div>
		<div class="value"><?php echo $typeOptions[$data['TeacherLeave']['teacher_leave_type_id']]; ?></div>
	</div>

	<div class="row">
        <div class="label"><?php echo __('From'); ?></div>
        <div class="value"><?php echo $this->Utility->formatDate($data['TeacherLeave']['date_from']); ?></div>
    </div>
	
	<div class="row">
        <div class="label"><?php echo __('To'); ?></div>
        <div class="value"><?php echo $this->Utility->formatDate($data['TeacherLeave']['date_to']); ?></div>
    </div>
	
	<div class="row">
        <div class="label"><?php echo __('Comments'); ?></div>
        <div class="value"><?php echo nl2br($data['TeacherLeave']['comments']); ?></div>
    </div>
	
	<div class="row">
        <div class="label"><?php echo __('Modified by'); ?></div>
        <div class="value"><?php echo trim($data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name']); ?></div>
    </div>
	
	<div class="row">
        <div class="label"><?php echo __('Modified on'); ?></div>
        <div class="value"><?php echo $data['TeacherLeave']['modified']; ?></div>
    </div>
	
	<div class="row">
        <div class="label"><?php echo __('Created by'); ?></div>
        <div class="value"><?php echo trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']); ?></div>
    </div>
	
	<div class="row">
        <div class="label"><?php echo __('Created on'); ?></div>
        <div class="value"><?php echo $data['TeacherLeave']['created']; ?></div>
    </div>
</div>