<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('attachments', 'stylesheet', array('inline' => false));
echo $this->Html->script('attachments', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="attachments" class="content_wrapper">
	<h1>
		<span><?php echo __('Attachments'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'attachmentsEdit'), array('class' => 'divider'));
		}
		?>
	</h1>
	
	<?php echo $this->element('alert'); ?>
		
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell cell_file"><?php echo __('File'); ?></div>
			<div class="table_cell cell_description"><?php echo __('Description'); ?></div>
			<div class="table_cell"><?php echo __('File Type'); ?></div>
			<div class="table_cell cell_date"><?php echo __('Uploaded On'); ?></div>
		</div>
					
		<div class="table_body">
			<?php
			foreach($data as $value) {
				$obj = $value[$_model];
				$fileext = strtolower(pathinfo($obj['file_name'], PATHINFO_EXTENSION));
				$ext = array_key_exists($fileext, $arrFileExtensions) ? $arrFileExtensions[$fileext] : $fileext;
				$link = $this->Html->link($obj['name'], array('action' => 'attachmentsDownload', $obj['id']));
			?>
			<div class="table_row">
				<div class="table_cell"><?php echo $link; ?></div>
				<div class="table_cell"><?php echo $obj['description']; ?></div>
				<div class="table_cell center"><?php echo  __($ext); ?></div>
				<div class="table_cell center"><?php echo $obj['created']; ?></div>
			</div>
			<?php }	?>
		</div>
	</div>
</div>
