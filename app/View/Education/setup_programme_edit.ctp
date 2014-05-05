<?php
echo $this->Html->css('table.old', 'stylesheet', array('inline' => false));
echo $this->Html->css('education', 'stylesheet', array('inline' => false));
echo $this->Html->script('education', false);
echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($pageTitle));
$this->start('contentActions');
echo $this->Html->link(__('Structure'), array('action' => 'index'), array('class' => 'divider'));
echo $this->Html->link(__('View'), array('action' => 'setup', $selectedOption), array('class' => 'divider'));
$this->end();
$this->assign('contentId', 'education_setup');
$this->assign('contentClass', 'edit');

$this->start('contentBody');
?>
<?php
echo $this->Form->create('Education', array(
		'id' => 'submitForm',
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
		'url' => array('controller' => 'Education', 'action' => 'setupEdit', $selectedOption)
	)
);
?>
<div id="params" class="none">
	<span name="category"><?php echo $selectedOption; ?></span>
</div>

<div class="row category">
	<?php
	echo $this->Form->input('category', array(
		'id' => 'category',
		'options' => $setupOptions,
		'default' => $selectedOption,
		'class' => 'form-control',
		'div' => 'col-md-4',
		'autocomplete' => 'off',
		'onchange' => 'education.navigateTo(this)'
	));
	?>
</div>

<?php 
$index = 0;
foreach($list as $systemId => $systemObj) {
?>
<fieldset class="section_group">
	<legend><?php echo $systemObj['name']; ?></legend>
	
	<?php 
	if(isset($systemObj['cycles'])) {
		foreach($systemObj['cycles'] as $cycleId => $cycleObj) { 
	?>
	<fieldset class="section_break" style="margin-bottom: 0;">
		<legend><?php echo $cycleObj['name']; ?></legend>

		<div class="params none">
			<span name="education_cycle_id"><?php echo $cycleId; ?></span>
		</div>

		<div class="table full_width">
			<div class="table_head">
				<div class="table_cell cell_visible"><?php echo __('Visible'); ?></div>
				<div class="table_cell cell_code"><?php echo __('Code'); ?></div>
				<div class="table_cell"><?php echo __($pageTitle); ?></div>
				<div class="table_cell cell_duration"><?php echo __('Duration'); ?></div>
				<div class="table_cell cell_grade_link"><?php echo __('Grades'); ?></div>
				<div class="table_cell cell_order"><?php echo __('Order'); ?></div>
			</div>
		</div>
		
		<?php
		echo $this->Utility->getListStart();
		foreach($cycleObj['programmes'] as $i => $obj) {
			$isVisible = $obj['visible']==1;
			$fieldName = sprintf('data[%s][%s][%%s]', $model, $index++);
			echo $this->Utility->getListRowStart($i, $isVisible);
			echo $this->Utility->getIdInput($this->Form, $fieldName, $obj['id']);
			echo $this->Utility->getOrderInput($this->Form, $fieldName, ($i+1));
			echo $this->Utility->getVisibleInput($this->Form, $fieldName, $isVisible);
			echo '<div class="cell cell_code"><div class="input_wrapper">';
			echo $this->Form->input('code', array('name' => sprintf($fieldName, 'code'), 'value' => $obj['code']));
			echo '</div></div>';
			echo '<div class="cell cell_programme"><div class="input_wrapper">';
			echo $this->Form->input('name', array('name' => sprintf($fieldName, 'name'), 'value' => $obj['name']));
			echo '</div></div>';
			echo '<div class="cell cell_duration"><div class="input_wrapper">';
			$inputOpts = array(
				'name' => sprintf($fieldName, 'duration'),
				'type' => 'text',
				'value' => $obj['duration'],
				'maxlength' => 2
			);
			echo $this->Form->input('duration', $inputOpts);
			echo '</div></div>';
			echo '<div class="cell cell_grade_link">';
			echo $this->Html->link(__('Grades'), array('action' => 'setupEdit', 'Grade', $obj['id']));
			echo '</div>';
			echo $this->Utility->getOrderControls();
			echo $this->Utility->getListRowEnd();
		}
		echo $this->Utility->getListEnd();
		if($_add) { echo $this->Utility->getAddRow($pageTitle); }
		?>
	</fieldset>
	<?php 
		} // end for(cycles)
	} // end if(cycles) 
	?>
</fieldset>
<?php } ?>

<div class="controls">
	<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'setup', $selectedOption), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>
