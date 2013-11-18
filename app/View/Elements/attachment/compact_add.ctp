<?php
$size = $params['size'];
$fieldName = sprintf('data[%s][%s][%%s]', $_model, $size);
?>

<div class="table_row <?php echo ($size+1)%2==0 ? 'even' : ''; ?>">
	<?php echo $this->Form->input('name', array('type'=>'hidden','name' => sprintf($fieldName, 'name'), 'label' => false, 'div' => false)); ?>
	<?php echo $this->Form->input('description', array('type'=>'hidden','name' => sprintf($fieldName, 'description'), 'label' => false, 'div' => false)); ?>
	
	<div class="table_cell">
		<div class="file_input file_index_<?php echo $size;?>">
			<input type="file" name="<?php echo 'files[' . $size . ']'; ?>" index="<?php echo $size;?>" onchange="attachments.updateFile(this);<?php echo $jsname;?>.validateFileSize(this);" onmouseout="attachments.updateFile(this)" />
			<div class="file">
				<div class="input_wrapper"><input type="text" /></div>
				<input type="button" class="btn" value="<?php echo __('Select File'); ?>" onclick="attachments.selectFile(this)" />
			</div>
		</div>
	</div>
	<div class="table_cell cell_delete"><span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="attachments.deleteRow(this)"></span></div>
</div>
