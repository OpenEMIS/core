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
	
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell cell_visible"><?php echo __('Visible'); ?></div>
			<div class="table_cell cell_subject_code"><?php echo __('Code'); ?></div>
			<div class="table_cell"><?php echo __($pageTitle); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($list as $obj) { ?>
			<div class="table_row<?php echo $obj['visible']!=1 ? ' inactive' : ''; ?>">
				<div class="table_cell cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']); ?></div>
				<div class="table_cell"><?php echo $obj['code']; ?></div>
				<div class="table_cell"><?php echo $obj['name']; ?></div>
			</div>
			<?php } ?>
		</div>
	</div>
	<?php echo $this->Form->end(); ?>
</div>
