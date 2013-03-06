<?php
$size = $params['size'];
$fieldName = sprintf('data[%s][%s][%%s]', $_model, $size);
?>

<div class="table_row <?php echo ($size+1)%2==0 ? 'even' : ''; ?>">

	<!-- <input type="hidden" name="<?php echo sprintf($fieldName,'visible'); ?>" value="0" /> -->
	<!-- <input type="hidden" name="<?php echo sprintf($fieldName,'name'); ?>" value="<?php //echo time()."_0_0"; ?>" /> -->
	<!-- <div class="table_cell cell_visible"> 
		<input type="radio" name="<?php // echo sprintf('data[%s][%s]', $_model,'visible'); ?>" value="<?php // echo $size; ?>"/>
	</div> -->
	<div class="table_cell cell_active">&nbsp;</div>
	<div class="table_cell cell_file">
		<div class="input_wrapper">
		<?php echo $this->Form->input('name', array('name' => sprintf($fieldName, 'name'), 'label' => false, 'div' => false)); ?>
		</div>
	</div>
	<!-- <div class="table_cell cell_description">
		<div class="input_wrapper">
		<?php // echo $this->Form->textarea('description', array('name' => sprintf($fieldName, 'description'), 'label' => false, 'div' => false)); ?>
		</div>
	</div> -->
	<div class="table_cell">
		<div class="file_input">
			<input type="file" name="<?php echo 'files[' . $size . ']'; ?>" onchange="dashboard.updateFile(this)" onmouseout="dashboard.updateFile(this)" />
			<div class="file">
				<div class="input_wrapper"><input type="text" /></div>
				<input type="button" class="btn" value="<?php echo __('Select File'); ?>" onclick="dashboard.selectFile(this)" />
			</div>
		</div>
	</div>
	<div class="table_cell cell_delete"><span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="dashboard.deleteRow(this)"></span></div>
</div>
