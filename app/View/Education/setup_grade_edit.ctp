<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('education', 'stylesheet', array('inline' => false));

echo $this->Html->script('education', false);
echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="education_setup" class="content_wrapper edit setup_grade">
	<?php
	echo $this->Form->create('Education', array(
		'id' => 'submitForm',
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Education', 'action' => 'setupEdit', 'Grade', $programmeId)
	));
	?>
	<h1>
		<span><?php echo __($pageTitle); ?></span>
		<?php
		echo $this->Html->link(__('Structure'), array('action' => 'index'), array('class' => 'divider'));
		echo $this->Html->link(__('View'), array('action' => 'setup', 'Grade', $programmeId), array('class' => 'divider'));
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div id="params" class="none">
		<span name="category">Grade</span>
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
	
	<fieldset class="section_group">
		<legend><?php echo $programmeName; ?></legend>
		
		<div class="params none">
			<span name="education_programme_id"><?php echo $programmeId ?></span>
		</div>
		
		<div class="table full_width">
			<div class="table_head">
				<div class="table_cell cell_visible"><?php echo __('Visible'); ?></div>
				<div class="table_cell"><?php echo __($pageTitle); ?></div>
				<div class="table_cell cell_subject_link"><?php echo __('Subjects'); ?></div>
				<div class="table_cell cell_order"><?php echo __('Order'); ?></div>
			</div>
		</div>
		
		<?php
		echo $this->Utility->getListStart();
		foreach($list as $i => $obj) {
			$isVisible = $obj['visible']==1;
			$fieldName = sprintf('data[%s][%s][%%s]', $model, $i);
			
			echo $this->Utility->getListRowStart($i, $isVisible);
			echo $this->Utility->getIdInput($this->Form, $fieldName, $obj['id']);
			echo $this->Form->hidden('education_programme_id', array(
				'name' => sprintf($fieldName, 'education_programme_id'),
				'value' => $programmeId
			));
			echo $this->Utility->getOrderInput($this->Form, $fieldName, ($i+1));
			echo $this->Utility->getVisibleInput($this->Form, $fieldName, $isVisible);
			echo $this->Utility->getNameInput($this->Form, $fieldName, $obj['name'], $isNameEditable);
			echo '<div class="cell cell_subject_link">';
			echo $this->Html->link(__('Subjects'), array('action' => 'setupEdit', 'GradeSubject', $programmeId, $obj['id']));
			echo '</div>';
			echo $this->Utility->getOrderControls();
			echo $this->Utility->getListRowEnd();
		}
		echo $this->Utility->getListEnd();
		if($_add) { echo $this->Utility->getAddRow($pageTitle); }
		?>
	</fieldset>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'setup', 'Grade', $programmeId), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
