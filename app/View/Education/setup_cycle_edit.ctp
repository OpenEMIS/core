<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('education', 'stylesheet', array('inline' => false));

echo $this->Html->script('education', false);
echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="education_setup" class="content_wrapper edit setup_cycle">
	<?php
	echo $this->Form->create('Education', array(
			'id' => 'submitForm',
			'inputDefaults' => array('label' => false, 'div' => false), 
			'url' => array('controller' => 'Education', 'action' => 'setupEdit', $selectedOption)
		)
	);
	?>
	<h1>
		<span><?php echo __($pageTitle); ?></span>
		<?php
		echo $this->Html->link(__('Structure'), array('action' => 'index'), array('class' => 'divider'));
		echo $this->Html->link(__('View'), array('action' => 'setup', $selectedOption), array('class' => 'divider'));
		?>
	</h1>
	
	<div id="params" class="none">
		<span name="category"><?php echo $selectedOption; ?></span>
	</div>
	
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
	
	<?php 
	$index = 0;
	foreach($list as $systemId => $systemObj) {
	?>
	<fieldset class="section_group">
		<legend><?php echo $systemObj['name']; ?></legend>
		
		<?php foreach($systemObj['levels'] as $levelId => $levelObj) { ?>
		<fieldset class="section_break" style="margin-bottom: 0;">
			<legend><?php echo $levelObj['name']; ?></legend>
			
			<div class="params none">
				<span name="education_level_id"><?php echo $levelId; ?></span>
			</div>
			
			<div class="table full_width">
				<div class="table_head">
					<div class="table_cell cell_visible"><?php echo __('Visible'); ?></div>
					<div class="table_cell"><?php echo __($pageTitle); ?></div>
					<div class="table_cell cell_admission_age"><?php echo __('Admission Age'); ?></div>
					<div class="table_cell cell_order"><?php echo __('Order'); ?></div>
				</div>
			</div>
			
			<?php
			echo $this->Utility->getListStart();
			foreach($levelObj['cycles'] as $i => $obj) {
				if($i === 'id') continue;
				$isVisible = $obj['visible']==1;
				$fieldName = sprintf('data[%s][%s][%%s]', $model, $index++);
				echo $this->Utility->getListRowStart($i, $isVisible);
				echo $this->Utility->getIdInput($this->Form, $fieldName, $obj['id']);
				echo $this->Utility->getOrderInput($this->Form, $fieldName, ($i+1));
				echo $this->Utility->getVisibleInput($this->Form, $fieldName, $isVisible);
				echo $this->Utility->getNameInput($this->Form, $fieldName, $obj['name'], $isNameEditable);
				
				echo '<div class="cell cell_admission_age">';
				echo $this->Form->input('admission_age', array(
					'name' => sprintf($fieldName, 'admission_age'),
					'value' => $obj['admission_age'],
					'before' => '<div class="input_wrapper">',
					'after' => '</div>',
					'maxlength' => 2
				));
				echo '</div>';
				
				echo $this->Utility->getOrderControls();
				echo $this->Utility->getListRowEnd();
			}
			echo $this->Utility->getListEnd();
			
			if($_add) { echo $this->Utility->getAddRow($pageTitle); }
			?>
		</fieldset>
		<?php } ?>
	</fieldset>
	<?php } ?>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'setup', $selectedOption), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
