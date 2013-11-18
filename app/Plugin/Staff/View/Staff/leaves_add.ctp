<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('attachments', 'stylesheet', array('inline' => false));
echo $this->Html->script('attachments', false);
?>
<?php echo $this->Html->script('/Staff/js/leaves', false); ?>

<?php echo $this->element('breadcrumb'); ?>
<div id="leaves" class="content_wrapper edit">
	<h1>
		<span><?php echo __('Leave'); ?></span>
	</h1>
	<?php echo $this->element('alert'); ?>

	<?php
	echo $this->Form->create('StaffLeave', array(
		'url' => array('controller' => 'Staff', 'action' => 'leavesAdd'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off')
	));
	?>
	
	<div class="row">
		<div class="label"><?php echo __('Type'); ?></div>
		<div class="value"><?php echo $this->Form->input('staff_leave_type_id', array('options' => $typeOptions, 'class' => 'default')); ?></div>
	</div>

	<div class="row">
		<div class="label"><?php echo __('Status'); ?></div>
		<div class="value"><?php echo $this->Form->input('leave_status_id', array('options' => $statusOptions, 'class' => 'default')); ?></div>
	</div>

	<div class="row">
		<div class="label"><?php echo __('First Day'); ?></div>
		<div class="value"><?php echo $this->Form->input('date_from', array('onchange'=>'objStaffLeaves.compute_work_days()', 'type' => 'date', 'dateFormat' => 'DMY', 'before' => '<div class="left">', 'after' => '</div>')); ?></div>
	</div>

	<div class="row">
		<div class="label"><?php echo __('Last Day'); ?></div>
		<div class="value"><?php echo $this->Form->input('date_to', array('onchange'=>'objStaffLeaves.compute_work_days()', 'type' => 'date', 'dateFormat' => 'DMY', 'selected' => date('Y-m-d', time()+86400))); ?></div>
	</div>

	<div class="row">
		<div class="label"><?php echo __('Days'); ?></div>
		<div class="value"><?php echo $this->Form->input('number_of_days', array('type'=>'text', 'class'=>'compute_days')); ?></div>
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
					$size =0; 
					$fieldName = sprintf('data[%s][%s][%%s]', $_model, $size);
				?>
				
				<div class="table_row <?php echo ($size+1)%2==0 ? 'even' : ''; ?>">
					<?php echo $this->Form->input('name', array('type'=>'hidden','name' => sprintf($fieldName, 'name'))); ?>
					<?php
					echo $this->Form->input('description', array('type'=>'hidden',
						'name' => sprintf($fieldName, 'description')
					));
					?>
					<div class="table_cell">
						<div class="file_input file_index_<?php echo $size;?>">
							<input type="file" name="<?php echo 'files[' . $size . ']'; ?>" index="<?php echo $size;?>" onchange="attachments.updateFile(this);objStaffLeaves.validateFileSize(this);" onmouseout="attachments.updateFile(this)" />
							<div class="file">
								<div class="input_wrapper"><input type="text" /></div>
								<input type="button" class="btn" value="<?php echo __('Select File'); ?>" onclick="attachments.selectFile(this)" />
							</div>
						</div>
					</div>
					<div class="table_cell cell_delete">
						<span class="icon_delete" title="<?php echo __("Delete"); ?>" onClick="attachments.deleteRow(this)"></span>
					</div>
				</div>
			</div>
		</div>

		<div class="table file_upload" style="width:240px;">
			<div class="table_body"></div>
		</div>
		<div style="color:#666666;font-size:10px;"><?php echo __('Note: Max upload file size is 2MB.'); ?></div> 
		<?php if($_add) { echo $this->Utility->getAddRow('Attachment'); } ?>
		</div>
	</div>

	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Save'); ?>"  onclick="js:if(objStaffLeaves.errorFlag()){ return true; }else{ return false; }"  class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'leaves'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>