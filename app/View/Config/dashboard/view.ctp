<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('attachments', 'stylesheet', array('inline' => false));
echo $this->Html->script('dashboard', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="attachments" class="content_wrapper">
	<h1>
		<span><?php echo __('Dashboard Image'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'dashboardEdit'), array('class' => 'divider'));
		}
		echo $this->Html->link(__('Back to Config'), array('controller' => 'Config', 'action' => 'index'), array('class' => 'divider', 'id' => 'back_to_config'));
		?>
	</h1>
	
	<?php echo $this->element('alert'); ?>
	<span id="controller" class="none"><?php echo $this->params['controller']; ?></span>
		
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell" style="width:35px;"><?php echo __('Active'); ?></div>
			<div class="table_cell" style="width:240px;"><?php echo __('File'); ?></div>
			<!-- <div class="table_cell cell_description"><?php echo __('Description'); ?></div> -->
			<div class="table_cell"><?php echo __('File Type'); ?></div>
			<div class="table_cell cell_date"><?php echo __('Uploaded On'); ?></div>
		</div>
					
		<div class="table_body">
			<?php
			foreach($data as $value) {
				$obj = $value[$_model];
				$fileext = strtolower(pathinfo($obj['file_name'], PATHINFO_EXTENSION));
				$ext = array_key_exists($fileext, $arrFileExtensions) ? $arrFileExtensions[$fileext] : $fileext;
				$link = $this->Html->link($obj['name'], array('action' => 'dashboardImage', $obj['id']));
			?>
			<div class="table_row">
				<div class="table_cell" style="text-align:center;"> 
					<?php if($obj['active'] > 0){ ?>
					<span class="green">✓</span>
					<?php } ?>
				</div>
				<div class="table_cell"><?php echo $link; ?></div>
				<!-- <div class="table_cell"><?php // echo $obj['description']; ?></div> -->
				<div class="table_cell center"><?php echo ($fileext == 'jpg')? __('JPEG'): strtoupper(__($fileext));//array_key_exists($fileext, $arrFileExtensions) ? $arrFileExtensions[$fileext] : $fileext; ?></div>
				<div class="table_cell center"><?php echo $obj['created']; ?></div>
			</div>
			<?php }	?>
		</div>
	</div>
</div>
