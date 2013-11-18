<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('attachments', 'stylesheet', array('inline' => false));
echo $this->Html->script('attachments', false);
?>
<?php echo $this->Html->script('/Teachers/js/leaves', false); ?>

<?php echo $this->element('breadcrumb'); ?>
<div id="leaves" class="content_wrapper">
	<h1>
		<span><?php echo __('Leave'); ?></span>
		<?php 
		if ($_edit) {
			echo $this->Html->link(__('Back'), array('action' => 'leavesView'), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>

	<?php
	echo $this->Form->create('TeacherLeave', array(
		'url' => array('controller' => 'Teachers', 'action' => 'leavesEdit', $this->request->data['TeacherLeave']['id']),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off')
	));
	$data = $this->request->data;
	?>
	
	<div class="row">
		<div class="label"><?php echo __('Type'); ?></div>
		<div class="value"><?php echo $this->Form->input('teacher_leave_type_id', array('options' => $typeOptions, 'class' => 'default')); ?></div>
	</div>

	<div class="row">
		<div class="label"><?php echo __('Status'); ?></div>
		<div class="value"><?php echo $this->Form->input('leave_status_id', array('options' => $statusOptions, 'class' => 'default')); ?></div>
	</div>

	<div class="row">
		<div class="label"><?php echo __('First Day'); ?></div>
		<div class="value"><?php echo $this->Form->input('date_from', array('onchange'=>'objTeacherLeaves.compute_work_days()','type' => 'date', 'dateFormat' => 'DMY', 'before' => '<div class="left">', 'after' => '</div>')); ?></div>
	</div>

	<div class="row">
		<div class="label"><?php echo __('Last Day'); ?></div>
		<div class="value"><?php echo $this->Form->input('date_to', array('onchange'=>'objTeacherLeaves.compute_work_days()','type' => 'date', 'dateFormat' => 'DMY')); ?></div>
	</div>

	<div class="row">
		<div class="label"><?php echo __('Days'); ?></div>
		<div class="value"><?php echo $this->Form->input('number_of_days', array('class'=>'compute_days')); ?></div>
	</div>
	
	<div class="row">
		<div class="label"><?php echo __('Comments'); ?></div>
		<div class="value"><?php echo $this->Form->input('comments', array('type' => 'textarea')); ?></div>
	</div>

	<span id="controller" class="none"><?php echo $this->params['controller']; ?></span>

	<div class="row">
		<div class="label"><?php echo __('Attachments'); ?></div>
		<div class="value">
		<div class="table " style="margin-bottom: -1px;width:240px;">
			<div class="table_head">
				<div class="table_cell"><?php echo __('File'); ?></div>
				<?php if($_delete) { ?>
					<div class="table_cell cell_delete">&nbsp;</div>
				<?php } ?>
			</div>
						
			<div class="table_body">
				<?php
				foreach($attachments as $index => $value){
					$obj = $value[$_model];
					$fileext = strtolower(pathinfo($obj['file_name'], PATHINFO_EXTENSION));
					$fieldName = sprintf('data[%s][%s][%%s]', $_model, $index);
				?>
				
				<div class="table_row" file-id="<?php echo $obj['id']; ?>">
					<?php echo $this->Utility->getIdInput($this->Form, $fieldName, $obj['id']); ?>
					<?php echo $this->Form->input('name', array('type'=>'hidden','name' => sprintf($fieldName, 'name'), 'value' => $obj['name'])); ?>
					<?php
					echo $this->Form->input('description', array('type'=>'hidden',
						'name' => sprintf($fieldName, 'description'),
						'value' => $obj['description']
					));
					?>
					<div class="table_cell center"><?php echo $this->Html->link($obj['name'], array('action' => 'attachmentsLeaveDownload', $obj['id']));?></div>
					<?php if($_delete) { ?>
					<div class="table_cell cell_delete">
						<span class="icon_delete" title="<?php echo __("Delete"); ?>" onClick="objTeacherLeaves.deleteFile(<?php echo $obj['id']; ?>)"></span>
					</div>
					<?php } ?>
				</div>
				<?php }	?>
			</div>
		</div>

		<div class="table file_upload" style="width:240px;">
			<div class="table_body"></div>
		</div>
		<?php if($_add) { echo $this->Utility->getAddRow('Attachment'); } ?>
		</div>
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
	
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Save'); ?>" onclick="js:if(objTeacherLeaves.errorFlag()){ return true; }else{ return false; }"   class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'leaves'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
</div>