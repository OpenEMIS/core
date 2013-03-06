<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('education', 'stylesheet', array('inline' => false));

echo $this->Html->script('education', false);
echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="education_setup" class="content_wrapper edit">
	<?php
	echo $this->Form->create('Education', array(
		'id' => 'submitForm',
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Education', 'action' => 'setupEdit', 'GradeSubject', $programmeId, $gradeId)
	));
	?>
	<h1>
		<span><?php echo __($pageTitle); ?></span>
		<?php
		echo $this->Html->link('Structure', array('action' => 'index'), array('class' => 'divider'));
		echo $this->Html->link('View', array('action' => 'setup', 'GradeSubject', $programmeId, $gradeId), array('class' => 'divider'));
		?>
	</h1>
	
	<div id="params" class="none">
		<span name="category">GradeSubject</span>
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
		<legend><?php echo $programmeName . ' - ' . $gradeName; ?></legend>
		
		<div class="params none">
			<span name="education_grade_id"><?php echo $gradeId; ?></span>
		</div>
		
		<div class="table full_width">
			<div class="table_head">
				<div class="table_cell cell_visible"><?php echo __('Visible'); ?></div>
				<div class="table_cell cell_subject_code"><?php echo __('Code'); ?></div>
				<div class="table_cell"><?php echo __('Subject'); ?></div>
				<div class="table_cell cell_hours"><?php echo __('Hours Required'); ?></div>
				<div class="table_cell cell_order"><?php echo __('Order'); ?></div>
			</div>
		</div>
		
		<?php
		echo $this->Utility->getListStart();
		foreach($list as $i => $obj) {
			$subject = $obj['EducationSubject'];
			$gradeSubject = $obj['EducationGradeSubject'];
			$isVisible = $gradeSubject['visible']==1;
			$fieldName = sprintf('data[%s][%s][%%s]', $model, $i);
			
			echo $this->Utility->getListRowStart($i, $isVisible);
			echo $this->Utility->getIdInput($this->Form, $fieldName, $gradeSubject['id']);
			echo $this->Utility->getOrderInput($this->Form, $fieldName, ($i+1));
			echo $this->Form->hidden('education_subject_id', array(
				'class' => 'EducationSubjectId',
				'name' => sprintf($fieldName, 'education_subject_id'),
				'value' => $gradeSubject['education_subject_id']
			));
			echo $this->Utility->getVisibleInput($this->Form, $fieldName, $isVisible);
			echo '<div class="cell cell_subject_code">' . $subject['code'] . '</div>';
			echo '<div class="cell cell_grade_subject">' . $subject['name'] . '</div>';
			echo $this->Form->input('hours_required', array(
				'name' => sprintf($fieldName, 'hours_required'),
				'type' => 'text',
				'value' => $gradeSubject['hours_required'],
				'before' => '<div class="cell cell_hours"><div class="input_wrapper">',
				'after' => '</div></div>',
				'maxlength' => '5',
				'onkeypress' => 'return utility.integerCheck(event)'
			));
			echo $this->Utility->getOrderControls();
			echo $this->Utility->getListRowEnd();
		}
		echo $this->Utility->getListEnd();
		if($_add) { echo $this->Utility->getAddRow('Subject'); }
		?>
	</fieldset>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'setup', 'GradeSubject', $programmeId, $gradeId), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>