<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="results" class="content_wrapper">
	<h1>
		<span><?php echo __('Results'); ?></span>
		<?php 
		echo $this->Html->link(__('Back'), array('action' => 'classesAssessments', $classId), array('class' => 'divider'));
		echo $this->Html->link(__('View'), array('action' => 'classesResults', $classId, $assessmentId, $selectedItem), array('class' => 'divider'));
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php
	echo $this->Form->create('AssessmentItemResult', array(
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
		'url' => array('controller' => 'InstitutionSites', 'action' => 'classesResultsEdit', $classId, $assessmentId, $selectedItem)
	));
	?>
	
	<?php if(!empty($itemOptions) && !empty($data)) { ?>
	<div class="row">
		<div class="label"><?php echo __('Subject'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('education_grade_subject_id', array(
				'name' => 'subject_id',
				'options' => $itemOptions,
				'default' => $selectedItem,
				'url' => sprintf('%s/%s/%d/%d', $this->params['controller'], $this->action, $classId, $assessmentId),
				'onchange' => 'jsForm.change(this)'
			));
			?>
		</div>
	</div>
	
	<div class="table full_width" style="margin-top: 15px;">
		<div class="table_head">
			<div class="table_cell cell_id_no"><?php echo __('OpenEMIS ID'); ?></div>
			<div class="table_cell"><?php echo __('Student Name'); ?></div>
			<div class="table_cell cell_marks"><?php echo __('Marks'); ?></div>
			<div class="table_cell cell_grading"><?php echo __('Grading'); ?></div>
		</div>
		<div class="table_body">
			<?php foreach($data as $i => $obj) { ?>
			<div class="table_row">
				<?php
				$result = $obj['AssessmentItemResult'];
				echo $this->Form->hidden($i.'.id', array('value' => $result['id']));
				echo $this->Form->hidden($i.'.student_id', array('value' => $obj['Student']['id']));
				echo $this->Form->hidden($i.'.school_year_id', array('value' => $obj['InstitutionSiteClass']['school_year_id']));
				?>
				<div class="table_cell middle"><?php echo $obj['Student']['identification_no']; ?></div>
				<div class="table_cell middle"><?php echo sprintf('%s %s %s', $obj['Student']['first_name'], $obj['Student']['middle_name'], $obj['Student']['last_name']); ?></div>
				<div class="table_cell">
					<div class="input_wrapper">
					<?php 
						echo $this->Form->input($i.'.marks', array(
							'value' => $obj['AssessmentItemResult']['marks'],
							'maxlength' => 4,
							'onkeypress' => 'return utility.integerCheck(event)'
						));
					?>
					</div>
				</div>
				<div class="table_cell">
				<?php 
					echo $this->Form->input($i.'.assessment_result_type_id', array(
						'class' => 'full_width',
						'options' => $gradingOptions,
						'default' => $result['assessment_result_type_id'],
					));
				?>
				</div>
			</div>
			<?php } ?>
		</div>
	</div>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'classesResults', $classId, $assessmentId, $selectedItem), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php } ?>
	<?php echo $this->Form->end(); ?>
</div>
