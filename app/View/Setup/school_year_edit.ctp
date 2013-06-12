<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('setup_variables', 'stylesheet', array('inline' => false));
echo $this->Html->css('combo_box', 'stylesheet', array('inline' => false));

echo $this->Html->script('app.date', false);
echo $this->Html->script('app.combo.box', false);
echo $this->Html->script('setup_variables', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="school_year" class="content_wrapper edit school_year">
	<?php
	echo $this->Form->create('SetupVariables', array(
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),	
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
	$yearList = $this->Utility->generateYearList('desc');
	?>
	
	<div class="combo_box_wrapper" id="year_list">
		<ul>
			<?php foreach($yearList as $id => $val) { ?>
			<li><?php echo $val; ?></li>
			<?php } ?>
		</ul>
	</div>
	
	<div class="table">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Year'); ?></div>
			<div class="table_cell cell_datepicker"><?php echo __('Dates'); ?></div>
			<div class="table_cell cell_days"><?php echo __('School Days'); ?></div>
			<div class="table_cell"><?php echo __('Current'); ?></div>
			<div class="table_cell"><?php echo __('Available'); ?></div>
		</div>
		
		<div class="table_body">
			<?php 
			foreach($list as $i => $obj) {
				$fieldName = sprintf('data[SchoolYear][%s][%%s]', $i);
			?>
			<div class="table_row">
				<?php echo $this->Form->hidden('id', array('name' => sprintf($fieldName, 'id'), 'value' => $obj['id'])); ?>
				<div class="table_cell">
					<div class="input_wrapper combo_box" rel="year_list">
						<?php
						echo $this->Form->input($i . '.name', array(
							'name' => sprintf($fieldName, 'name'),
							'value' => $obj['name']
						));
						?>
					</div>
				</div>
				<div class="table_cell">
					<div class="table_cell_row">
						<div class="label"><?php echo __('From'); ?></div>
						<?php 
						echo $this->Utility->getDatePicker($this->Form, $i . 'start_date', 
							array(
								'name' => sprintf($fieldName, 'start_date'),
								'value' => $obj['start_date'],
								'endDateValidation' => $i . 'end_date'
							));
						?>
					</div>
					<div class="table_cell_row">
						<div class="label"><?php echo __('To'); ?></div>
						<?php 
						echo $this->Utility->getDatePicker($this->Form, $i . 'end_date', 
							array(
								'name' => sprintf($fieldName, 'end_date'),
								'emptySelect' => true,
								'value' => $obj['end_date'],
								'endDateValidation' => $i . 'end_date',
								'yearAdjust' => 1
							));
						?>
					</div>
				</div>
				<div class="table_cell">
					<div class="input_wrapper">
						<?php
						echo $this->Form->input($i . '.school_days', array(
							'name' => sprintf($fieldName, 'school_days'),
							'type' => 'text',
							'maxlength' => 5,
							'value' => $obj['school_days'],
							'onkeypress' => 'return utility.integerCheck(event)'
						));
						?>
					</div>
				</div>
				<div class="table_cell">
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
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'setupVariables', $selectedCategory), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
