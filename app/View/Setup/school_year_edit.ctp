<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('setup_variables', 'stylesheet', array('inline' => false));

echo $this->Html->script('app.date', false);
echo $this->Html->script('setup_variables', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="school_year" class="content_wrapper edit school_year">
	<?php
	echo $this->Form->create('SetupVariables', array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Setup', 'action' => 'setupVariablesEdit')
	));
	?>
	<h1>
		<span><?php echo __($header); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'setupVariables', $selectedCategory), array('class' => 'divider')); ?>
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
	
	<?php 
	$list = $category['School Year']['items']['School Year']['options'];
	$fieldName = 'data[SchoolYear][%s][%%s]';
	?>
	
	<div class="table">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Year'); ?></div>
			<div class="table_cell cell_datepicker"><?php echo __('Start Date'); ?></div>
			<div class="table_cell cell_datepicker"><?php echo __('End Date'); ?></div>
			<div class="table_cell cell_current"><?php echo __('Current'); ?></div>
			<div class="table_cell cell_available"><?php echo __('Available'); ?></div>
		</div>
		
		<div class="table_body">
			<?php 
			foreach($list as $i => $obj) {
				$fieldName = sprintf('data[SchoolYear][%s][%%s]', $i);
			?>
			<div class="table_row">
				<?php echo $this->Form->hidden('id', array('name' => sprintf($fieldName, 'id'), 'value' => $obj['id'])); ?>
				<div class="table_cell"><?php echo $obj['name']; ?></div>
				<div class="table_cell">
					<?php 
					echo $this->Utility->getDatePicker($this->Form, $i.'start_date', 
						array(
							'name' => sprintf($fieldName, 'start_date'), 
							'value' => $obj['start_date'],
							'endDateValidation' => $i.'end_date'
						));
					?>
				</div>
				<div class="table_cell">
					<?php 
					echo $this->Utility->getDatePicker($this->Form, $i.'end_date', 
						array(
							'name' => sprintf($fieldName, 'end_date'),
							'value' => $obj['end_date'],
							'endDateValidation' => $i.'end_date',
							'yearAdjust' => 1
						));
					?>
				</div>
				<div class="table_cell ">
					<?php
					$attr = array(
						'label' => false, 
						'name' => sprintf($fieldName, 'current'),
						'class' => 'input_radio', 
						'autocomplete' => 'off',
						'onchange' => 'setup.toggleRadio(this)'
					);
					if($obj['current'] == 1) {
						$attr['checked'] = 'checked';
					}
					echo $this->Form->radio('current', array('1' => ''), $attr);
					?>
				</div>
				<div class="table_cell">
					<?php
					$inputOpts = array(
						'name' => sprintf($fieldName, 'available'),
						'value' => 1,
						'autocomplete' => 'off'
					);
					
					if($obj['available']==1) {
						$inputOpts['checked'] = 'checked';
					}
					echo $this->Form->checkbox('available', $inputOpts);
					?>
				</div>
			</div>
			<?php } ?>
		</div>
	</div>
	
	<?php if($_add) { ?>
	<div class="row" style="margin-top: 10px;"><a class="void icon_plus"><?php echo __('Add') . ' ' . __('School Year'); ?></a></div>
	<?php } ?>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onclick="return setup.updateYear();" />
		<input type="button" value="<?php echo __('Cancel'); ?>" class="btn_cancel btn_left" />
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
