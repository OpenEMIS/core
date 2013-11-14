<?php echo $this->element('breadcrumb'); ?>
<div id="leaves" class="content_wrapper">
	<h1>
		<span><?php echo __('Leave'); ?></span>
		<?php
		echo $this->Html->link(__('Back'), array('action' => 'leaves'), array('class' => 'divider'));
		if ($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'leavesEdit', $data['StaffLeave']['id']), array('class' => 'divider'));
		}
		if($_delete) {
			echo $this->Html->link(__('Delete'), array('action' => 'leavesDelete', $data['StaffLeave']['id']), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>

	<div class="row">
		<div class="label"><?php echo __('Type'); ?></div>
		<div class="value"><?php echo $typeOptions[$data['StaffLeave']['staff_leave_type_id']]; ?></div>
	</div>

	<div class="row">
		<div class="label"><?php echo __('Status'); ?></div>
		<div class="value"><?php echo $statusOptions[$data['StaffLeave']['leave_status_id']]; ?></div>
	</div>

	<div class="row">
        <div class="label"><?php echo __('First Day'); ?></div>
        <div class="value"><?php echo $this->Utility->formatDate($data['StaffLeave']['date_from']); ?></div>
    </div>
	
	<div class="row">
        <div class="label"><?php echo __('Last Day'); ?></div>
        <div class="value"><?php echo $this->Utility->formatDate($data['StaffLeave']['date_to']); ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Days'); ?></div>
        <div class="value"><?php echo $data['StaffLeave']['number_of_days']; ?></div>
    </div>

    <?php if(!empty($attachments)){?>
		<div class="row">
	        <div class="label"><?php echo __('Attachments'); ?></div>
	        <div class="value">
	        <?php foreach($attachments as $key=>$value){ 
		        $obj = $value[$_model];
				$link = $this->Html->link($obj['name'], array('action' => 'attachmentsLeaveDownload', $obj['id']));
		        echo $link . '<br />'; 
	        } ?>
	    	</div>
	    </div>
    <?php }?>
	
	<div class="row">
        <div class="label"><?php echo __('Comments'); ?></div>
        <div class="value"><?php echo $data['StaffLeave']['comments']; ?></div>
    </div>
	
	<div class="row">
        <div class="label"><?php echo __('Modified by'); ?></div>
        <div class="value"><?php echo trim($data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name']); ?></div>
    </div>
	
	<div class="row">
        <div class="label"><?php echo __('Modified on'); ?></div>
        <div class="value"><?php echo $data['StaffLeave']['modified']; ?></div>
    </div>
	
	<div class="row">
        <div class="label"><?php echo __('Created by'); ?></div>
        <div class="value"><?php echo trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']); ?></div>
    </div>
	
	<div class="row">
        <div class="label"><?php echo __('Created on'); ?></div>
        <div class="value"><?php echo $data['StaffLeave']['created']; ?></div>
    </div>
</div>