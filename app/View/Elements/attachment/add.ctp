<?php
$size = $params['size'];
$fieldName = sprintf('data[%s][%s][%%s]', $_model, $size);
?>

<div class="table_row <?php echo ($size+1)%2==0 ? 'even' : ''; ?>">
	<div class="table_cell cell_file">
		<div class="input_wrapper">
		<?php echo $this->Form->input('name', array('name' => sprintf($fieldName, 'name'), 'label' => false, 'div' => false)); ?>
		</div>
	</div>
	<div class="table_cell cell_description">
		<div class="input_wrapper">
		<?php echo $this->Form->textarea('description', array('name' => sprintf($fieldName, 'description'), 'label' => false, 'div' => false)); ?>
		</div>
	</div>
	<div class="table_cell">
		<div class="file_input">
			<input type="file" name="<?php echo 'files[' . $size . ']'; ?>" onchange="attachments.updateFile(this)" onmouseout="attachments.updateFile(this)" />
			<div class="file">
				<div class="input_wrapper"><input type="text" /></div>
				<input type="button" class="btn" value="<?php echo __('Select File'); ?>" onclick="attachments.selectFile(this)" />
			</div>
		</div>
	</div>
	<div class="table_cell cell_delete"><span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="attachments.deleteRow(this)"></span></div>
</div>
