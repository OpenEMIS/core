<?php echo $this->element('breadcrumb'); ?>
<div id="leaves" class="content_wrapper">
	<h1>
		<span><?php echo __('Leave'); ?></span>
		<?php
		echo $this->Html->link(__('Back'), array('action' => 'leaves'), array('class' => 'divider'));
		if ($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'leavesEdit', $data['id']), array('class' => 'divider'));
		}
		if($_delete) {
			echo $this->Html->link(__('Delete'), array('action' => 'leavesDelete', $data['id']), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>

	<div class="row">
		<div class="label"><?php echo __('Type'); ?></div>
		<div class="value"><?php echo $typeOptions[$data['staff_leave_type_id']]; ?></div>
	</div>

	<div class="row">
        <div class="label"><?php echo __('From'); ?></div>
        <div class="value"><?php echo $this->Utility->formatDate($data['date_from']); ?></div>
    </div>
	
	<div class="row">
        <div class="label"><?php echo __('To'); ?></div>
        <div class="value"><?php echo $this->Utility->formatDate($data['date_to']); ?></div>
    </div>
	
	<div class="row">
        <div class="label"><?php echo __('Comments'); ?></div>
        <div class="value"><?php echo $data['comments']; ?></div>
    </div>
</div>