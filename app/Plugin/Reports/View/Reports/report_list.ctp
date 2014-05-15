<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div class="content_wrapper" id="report-list">
	<h1>
		<span><?php echo __('Generated Files'); ?></span>
	</h1>
	<?php echo $this->element('alert'); ?>
    <?php if(count($files) > 0) {?>
	<div class="table full_width <?php echo $_execute ? 'allow_hover' : ''; ?>" action="Reports/<?php echo $this->action; ?>Download/">
		<div class="table_head">
			<div class="table_cell col_name"><?php echo __('Name'); ?></div>
			<div class="table_cell col_name"><?php echo __('File size'); ?></div>
			<div class="table_cell col_lastgen"><?php echo __('File Type'); ?></div>
			<div class="table_cell col_name"><?php echo __('Generated'); ?></div>
		</div>
		<?php foreach($files as $fileType => $arrV){ ?>
		
		<div class="table_body">
		<?php foreach($arrV as $time => $arrFileInfo){	?>
		
			<div row-id="<?php echo $arrFileInfo['basename'];?>" class="table_row">
				<div class="table_cell col_name"><?php echo $arrFileInfo['name'];?></div>
				<div class="table_cell col_name center"><?php echo $arrFileInfo['size'];?></div>
				<div class="table_cell col_lastgen center"><?php echo $fileType;?></div>
				<div class="table_cell col_name center"><?php echo $arrFileInfo['time'];?></div>  
			</div>
		
		<?php } ?>
		</div>
		
		<?php } ?>
	</div>
	<?php } ?>
</div>