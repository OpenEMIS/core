<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('setup_variables', 'stylesheet', array('inline' => false));

echo $this->Html->script('setup_variables', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="setup-variables" class="content_wrapper">
	<?php
	echo $this->Form->create('SetupVariables', array(
		'id' => 'submitForm',
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Setup', 'action' => 'setupVariables')
	));
	?>
	<h1>
		<span><?php echo __('Setup Variables'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'setupVariablesEdit'), array('id' => 'edit-link', 'class' => 'divider'));
		}
		?>
	</h1>
	
	<div class="row category">
		<?php
		echo $this->Form->input('category', array(
			'id' => 'category',
			'options' => $categoryList,
			'default' => $selectedCategory,
			'onchange' => 'setup.changeCategory()'
		));
		?>
	</div>
	
	<?php foreach($category as $type) { ?>
		<?php foreach($type['items'] as $typeName => $options) { ?>
		<fieldset class="section_group">
			<legend><?php echo __($typeName) ?></legend>
			
			<div class="table">
				<div class="table_head">
					<div class="table_cell cell_visible"><?php echo __('Visible'); ?></div>
					<div class="table_cell cell_option"><?php echo __('Option'); ?></div>
				</div>
				
				<div class="table_body">
					<?php foreach($options['options'] as $obj) { ?>
					<div class="table_row<?php echo $obj['visible']!=1 ? ' inactive' : ''; ?>">
						<div class="table_cell cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']==1); ?></div>
						<div class="table_cell"><?php echo $obj['name'] ?></div>
					</div>
					<?php } ?>
				</div>
			</div>
		</fieldset>
		<?php } ?>
	<?php } ?>
	
	<?php echo $this->Form->end(); ?>
</div>