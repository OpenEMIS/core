<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Results'));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'classesAssessments', $classId), array('class' => 'divider'));
echo $this->Html->link(__('View'), array('action' => 'classesResults', $classId, $assessmentId, $selectedItem), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>

<div id="results" class="content_wrapper">

	<?php
	echo $this->Form->create('AssessmentItemResult', array(
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
		'url' => array('controller' => 'InstitutionSites', 'action' => 'classesResultsEdit', $classId, $assessmentId, $selectedItem),
		'class' => 'form-horizontal'
	));
	?>

	<?php if (!empty($itemOptions) && !empty($data)) { ?>
		<div class="form-group">
			<label class="control-label col-md-3"><?php echo __('Subject'); ?></label>
			<div class="col-md-4">
				<?php
				echo $this->Form->input('education_grade_subject_id', array(
					'name' => 'subject_id',
					'options' => $itemOptions,
					'default' => $selectedItem,
					'class' => 'form-control',
					'url' => sprintf('%s/%s/%d/%d', $this->params['controller'], $this->action, $classId, $assessmentId),
					'onchange' => 'jsForm.change(this)'
				));
				?>
			</div>
		</div>

		<table class="table table-striped table-hover table-bordered" style="margin-top: 15px;">
			<thead>
				<tr>
					<td class="table_cell cell_id_no"><?php echo __('OpenEMIS ID'); ?></td>
					<td class="table_cell"><?php echo __('Student Name'); ?></td>
					<td class="table_cell cell_marks"><?php echo __('Marks'); ?></td>
					<td class="table_cell cell_grading"><?php echo __('Grading'); ?></td>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($data as $i => $obj) { ?>
					<tr>
						<?php
						$result = $obj['AssessmentItemResult'];
						echo $this->Form->hidden($i . '.id', array('value' => $result['id']));
						echo $this->Form->hidden($i . '.student_id', array('value' => $obj['Student']['id']));
						echo $this->Form->hidden($i . '.school_year_id', array('value' => $obj['InstitutionSiteClass']['school_year_id']));
						?>
						<td class="table_cell middle"><?php echo $obj['Student']['identification_no']; ?></td>
						<td class="table_cell middle"><?php echo sprintf('%s %s %s', $obj['Student']['first_name'], $obj['Student']['middle_name'], $obj['Student']['last_name']); ?></td>
						<td class="table_cell">
							<?php
								echo $this->Form->input($i . '.marks', array(
									'value' => $obj['AssessmentItemResult']['marks'],
									'class' => 'form-control',
									'maxlength' => 4,
									'onkeypress' => 'return utility.integerCheck(event)'
								));
								?>
						</td>
						<td class="table_cell">
							<?php
							echo $this->Form->input($i . '.assessment_result_type_id', array(
								'class' => 'full_width form-control',
								'options' => $gradingOptions,
								'default' => $result['assessment_result_type_id'],
							));
							?>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>

		<div class="controls">
			<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
			<?php echo $this->Html->link(__('Cancel'), array('action' => 'classesResults', $classId, $assessmentId, $selectedItem), array('class' => 'btn_cancel btn_left')); ?>
		</div>
	<?php } ?>
	<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>
