<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('setup_variables', 'stylesheet', array('inline' => false));

echo $this->Html->script('setup_variables', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="setup-variables" class="content_wrapper">
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
	<?php echo $this->element('alert'); ?>
	
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
	
	<?php foreach($category as $type) { ?>
		<?php foreach($type['items'] as $typeName => $options) { ?>
		<fieldset class="section_group">
			<legend><?php echo __($typeName); ?></legend>
			
			<?php foreach($options['options'] as $categoryName => $categories) { ?>
			<fieldset class="section_break">
				<legend><?php echo __($categoryName) ?></legend>
				<div class="table">
					<div class="table_head">
						<div class="table_cell cell_visible"><?php echo __('Visible'); ?></div>
						<div class="table_cell cell_option"><?php echo __('Option'); ?></div>
                        <div class="table_cell cell_national_code"><?php echo __('National Code'); ?></div>
                    	<div class="table_cell cell_international_code"><?php echo __('International Code'); ?></div>
					</div>
					
					<div class="table_body">
						<?php foreach($categories['options'] as $obj) { ?>
						<div class="table_row<?php echo $obj['visible']!=1 ? ' inactive' : ''; ?>">
							<div class="table_cell cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']==1); ?></div>
							<div class="table_cell"><?php echo $obj['name'] ?></div>
                            <div class="table_cell cell_national_code"><?php echo $obj['national_code'] ?></div>
                        	<div class="table_cell cell_international_code"><?php echo $obj['international_code'] ?></div>
						</div>
						<?php } ?>
					</div>
				</div>
			</fieldset>
			<?php } ?>
		</fieldset>
		<?php } ?>
	<?php } ?>
	
	<?php echo $this->Form->end(); ?>
</div>
