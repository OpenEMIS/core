<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('setup_variables', 'stylesheet', array('inline' => false));

echo $this->Html->script('setup_variables', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="school_year" class="content_wrapper school_year">
	<?php
	echo $this->Form->create('SetupVariables', array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Setup', 'action' => 'setupVariables')
	));
	?>
	<h1>
		<span><?php echo __($header); ?></span>
		<?php 
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'setupVariablesEdit', $selectedCategory), array('class' => 'divider'));
		}
		?>
	</h1>
	
	<div class="row category">
		<?php
		echo $this->Form->input('category', array(
			'id' => 'category',
			'options' => $categoryList,
			'default' => $selectedCategory,
			'url' => 'Setup/setupVariables/',
			'onchange' => 'setup.changeCategory()'
		));
		?>
	</div>
	
	<?php $list = $category['School Year']['items']['School Year']['options']; ?>
	
	<div class="table">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Year'); ?></div>
			<div class="table_cell cell_start_date"><?php echo __('Start Date'); ?></div>
			<div class="table_cell cell_end_date"><?php echo __('End Date'); ?></div>
			<div class="table_cell"><?php echo __('Current'); ?></div>
			<div class="table_cell"><?php echo __('Available'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($list as $row) { ?>
			<div class="table_row">
				<div class="table_cell"><?php echo $row['name'] ?></div>
				<div class="table_cell"><?php echo $this->Utility->formatDate($row['start_date']); ?></div>
				<div class="table_cell"><?php echo $this->Utility->formatDate($row['end_date']); ?></div>
				<div class="table_cell"><?php echo $this->Utility->checkOrCrossMarker($row['current']); ?></div>
				<div class="table_cell"><?php echo $this->Utility->checkOrCrossMarker($row['available']); ?></div>
			</div>
			<?php } ?>
		</div>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
