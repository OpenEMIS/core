<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('education', 'stylesheet', array('inline' => false));

echo $this->Html->script('education', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="education_setup" class="content_wrapper">
	<?php
	echo $this->Form->create('Education', array(
			'id' => 'submitForm',
			'inputDefaults' => array('label' => false, 'div' => false),	
			'url' => array('controller' => 'Education', 'action' => 'setup')
		)
	);
	?>
	<h1>
		<span><?php echo __($pageTitle); ?></span>
		<?php
		echo $this->Html->link(__('Structure'), array('action' => 'index'), array('class' => 'divider'));
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'setupEdit', $selectedOption), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="row category">
		<?php
		echo $this->Form->input('category', array(
			'id' => 'category',
			'options' => $setupOptions,
			'default' => $selectedOption,
			'autocomplete' => 'off',
			'onchange' => 'education.navigateTo(this)'
		));
		?>
	</div>
	
	<?php foreach($list as $systemId => $systemObj) { ?>
	<fieldset class="section_group">
		<legend><?php echo $systemObj['name']; ?></legend>
		
		<?php foreach($systemObj['cycles'] as $cycleId => $cycleObj) { ?>
		<fieldset class="section_break">
			<legend><?php echo $cycleObj['name']; ?></legend>
			
			<div class="table">
				<div class="table_head">
					<div class="table_cell cell_visible"><?php echo __('Visible'); ?></div>
					<div class="table_cell"><?php echo __($pageTitle); ?></div>
					<div class="table_cell cell_duration"><?php echo __('Duration'); ?></div>
					<div class="table_cell cell_grade_link"><?php echo __('Grades'); ?></div>
				</div>
				
				<div class="table_body">
					<?php foreach($cycleObj['programmes'] as $key => $obj) { ?>
					<div class="table_row<?php echo $obj['visible']!=1 ? ' inactive' : ''; ?>">
						<div class="table_cell cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']); ?></div>
						<div class="table_cell"><?php echo $obj['name']; ?></div>
						<div class="table_cell center"><?php echo $obj['duration'] . ' years'; ?></div>
						<div class="table_cell center">
							<?php echo $this->Html->link(__('Grades'), array('action' => 'setup', 'Grade', $obj['id'])); ?>
						</div>
					</div>
					<?php } ?>
				</div>
			</div>
		</fieldset> <!-- end section break -->
		<?php } ?>
	</fieldset> <!-- end section group -->
	<?php } ?>
	
	<?php echo $this->Form->end(); ?>
</div>
