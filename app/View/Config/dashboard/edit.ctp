<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('attachments', 'stylesheet', array('inline' => false));
echo $this->Html->css('config-attachments', 'stylesheet', array('inline' => false));
echo $this->Html->script('dashboard', false);
?>
<?php echo $this->element('breadcrumb'); ?>

<div id="attachments" class="content_wrapper">
	<?php
	echo $this->Form->create(null, array(
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false), 
		'url' => array('controller' => $this->params['controller'], 'action' => 'dashboardEdit')
	));
	?>
	<!-- <input type="hidden" name="MAX_FILE_SIZE" value="500" /> -->
	<h1>
		<span><?php echo __('Dashboard Image'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'dashboard'), array('class' => 'divider')); ?>
	</h1>
	
	<?php echo $this->element('alert'); ?>
	
	<span id="controller" class="none"><?php echo $this->params['controller']; ?></span>
		
	<div class="table full_width" style="margin-bottom: -1px;">
		<div class="table_head">
			<div class="table_cell cell_active">&nbsp;</div>
			<div class="table_cell cell_file"><?php echo __('File'); ?></div>
			<!-- <div class="table_cell cell_description"><?php echo __('Description'); ?></div> -->
			<div class="table_cell"><?php echo __('File Type'); ?></div>
			<div class="table_cell cell_date"><?php echo __('Uploaded On'); ?></div>
			<div class="table_cell cell_delete">&nbsp;</div>
		</div>
					
		<div class="table_body">
			<?php
			foreach($data as $index => $value){
				$obj = $value['ConfigAttachment'];
				$fileext = strtolower(pathinfo($obj['file_name'], PATHINFO_EXTENSION));
				$fieldName = sprintf('data[%s][%s][%%s]', $_model, $index);
			?>
			
			<div class="table_row" file-id="<?php echo $obj['id']; ?>">
				<?php echo $this->Utility->getIdInput($this->Form, $fieldName, $obj['id']); ?>
				<div class="table_cell"> 
					<input type="radio" name="<?php echo sprintf('data[%s][%s]', $_model,'visible'); ?>" <?php echo ($obj['active'])? 'checked="checked"':''; ?> value="<?php echo $index; ?>"/>
				</div>
				<div class="table_cell">
					<div class="input_wrapper">
					<?php echo $this->Form->input('name', array('name' => sprintf($fieldName, 'name'), 'value' => $obj['name'])); ?>
					</div>
					<!-- <div class="input_wrapper">
					<?php // echo $this->Form->input('name', array('name' => sprintf($fieldName, 'name'), 'value' => $obj['name'])); ?>
					</div> -->
				</div>
				<div class="table_cell center"><?php echo ($fileext == 'jpg')? 'JPEG': strtoupper($fileext);//array_key_exists($fileext, $arrFileExtensions) ? $arrFileExtensions[$fileext] : $fileext; ?></div>
				<div class="table_cell center"><?php echo $obj['created']; ?></div>
				<div class="table_cell cell_delete">
					<?php if($_delete) { ?>
					<?php if($imageConfig['dashboard_img_default'] != $obj['id']) { ?>
					<span class="icon_delete" title="<?php echo __("Delete"); ?>" onClick="dashboard.deleteFile(<?php echo $obj['id']; ?>)"></span>
					<?php } ?>
					<?php } ?>
				</div>
			</div>
			<?php }	?>
		</div>
	</div>
	
	<div class="table full_width file_upload">
		<div class="table_body"></div>
	</div>
	
	<?php if($_add) { echo $this->Utility->getAddRow('Image'); } ?>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'dashboard'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
</div>
