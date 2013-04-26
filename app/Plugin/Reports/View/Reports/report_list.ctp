<?php 

echo $this->Html->css('table', 'stylesheet', array('inline' => false));


?>
<?php echo $this->element('breadcrumb'); ?>

<div class="content_wrapper" id="report-list">
	<h1>
		<span>Institution Reports</span>
	</h1>
	<?php echo $this->element('alert'); ?>
    <?php if(count($files) > 0) {?>
	<div action="Reports/download/" class="table allow_hover full_width">
				<div class="table_head">
						<div class="table_cell col_name">Name</div>
						<div class="table_cell col_name">File size</div>
						<div class="table_cell col_lastgen">File Type</div>
						<div class="table_cell col_name">Generated</div>
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