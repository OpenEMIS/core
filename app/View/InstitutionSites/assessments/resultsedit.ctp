<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $educationGradeName . ' - ' . $assessmentName);

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'assessments'), array('class' => 'divider'));
echo $this->Html->link(__('View'), array('action' => 'assessmentsResults', $selectedAcademicPeriod, $assessmentId, $selectedClass, $selectedItem), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => 'assessmentsResultsEdit', $selectedAcademicPeriod, $assessmentId, $selectedClass, $selectedItem));
echo $this->Form->create($model, $formOptions);
?>

<div class="row page-controls">
		<?php
		echo $this->Form->input('class_id', array(
			'options' => $classOptions,
			'label' => false,
			'div' => false,
			'value' => $selectedClass,
			'class' => 'form-control',
			'onchange' => 'jsForm.change(this)',
			'url' => $this->params['controller'] . '/' . $this->action . '/' . $selectedAcademicPeriod . '/' . $assessmentId
		));
		?>
		<?php
		echo $this->Form->input('assessment_item_id', array(
			'options' => $itemOptions,
			'label' => false,
			'div' => false,
			'value' => $selectedItem,
			'class' => 'form-control',
			'onchange' => 'jsForm.change(this)',
			'url' => $this->params['controller'] . '/' . $this->action . '/' . $selectedAcademicPeriod . '/' . $assessmentId . '/' . $selectedClass
		));
		?>
</div>

<table class="table table-striped table-hover table-bordered">
	<thead>
		<tr>
			<th class="cell_id_no"><?php echo __('OpenEMIS ID'); ?></th>
			<th class=""><?php echo __('Student Name'); ?></th>
			<th class="cell_marks">
				<?php 
				echo sprintf('%s (%s=%d, %s=%d)', __('Marks'), __('Pass'), $minMarks, __('Max'), $maxMarks); 
				?>
			</th>
			<th class="cell_grading"><?php echo __('Grading'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($data as $i => $obj) { ?>
			<tr>
				<?php
				$result = $obj['AssessmentItemResult'];
				echo $this->Form->hidden($i . '.id', array('value' => $result['id']));
				echo $this->Form->hidden($i . '.student_id', array('value' => $obj['Student']['id']));
				echo $this->Form->hidden($i . '.academic_period_id', array('value' => $obj['InstitutionSiteClass']['academic_period_id']));
				?>
				<td class="middle"><?php echo $obj['SecurityUser']['openemis_no']; ?></td>
				<td class="middle"><?php echo $this->Model->getName($obj['Student']); ?></td>
				<td class="input">
					<?php
					echo $this->Form->input($i . '.marks', array(
						'label' => false,
						'div' => false,
						'value' => $obj['AssessmentItemResult']['marks'],
						'class' => 'form-control',
						'maxlength' => 4,
						'onkeypress' => 'return utility.integerCheck(event)'
					));
					?>
				</td>
				<td class="input">
					<?php
					echo $this->Form->input($i . '.assessment_result_type_id', array(
						'label' => false,
						'div' => false,
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

<?php echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'assessmentsResults', $selectedAcademicPeriod, $assessmentId, $selectedClass, $selectedItem))); ?>
<?php
echo $this->Form->end();
$this->end();
?>
