<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('home', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="user_details" class="content_wrapper">
	<h1><?php echo __($subTitle); ?></h1>
		
		<div class="table help">
			<div class="table_body">
		
				<div class="table_row">
					<div class="table_cell cell_item_name"><?php echo __('Database'); ?></div>
					<div class="table_cell"><?php echo $db_store . '/' . $db_version ?></div>
				</div>
				<!-- <div class="table_row">
					<div class="table_cell cell_item_name">Database Client</div>
					<div class="table_cell">libmysql - 5.1.66</div>
				</div> -->
				<div class="table_row">
					<div class="table_cell cell_item_name"><?php echo __('PHP Version'); ?></div>
					<div class="table_cell"><?php echo phpversion() ?></div>
				</div>
				<div class="table_row">
					<div class="table_cell cell_item_name"><?php echo __('Web Server'); ?></div>
					<div class="table_cell"><?php echo $_SERVER['SERVER_SOFTWARE'];?></div>
				</div>
				<div class="table_row">
					<div class="table_cell cell_item_name"><?php echo __('Operating System'); ?></div>
					<div class="table_cell"><?php echo php_uname("s") . '/' . php_uname("r"); ?></div>
				</div>
			</div>
		</div>
	
</div>