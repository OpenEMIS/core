<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $educationGradeName . ' - ' . $assessmentName);

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'assessments'), array('class' => 'divider'));
echo $this->Html->link(__('View'), array('action' => 'assessmentsResults', $selectedYear, $assessmentId, $selectedClass, $selectedItem), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => 'assessmentsResultsEdit', $selectedYear, $assessmentId, $selectedClass, $selectedItem));
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
			'url' => $this->params['controller'] . '/' . $this->action . '/' . $selectedYear . '/' . $assessmentId
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
			'url' => $this->params['controller'] . '/' . $this->action . '/' . $selectedYear . '/' . $assessmentId . '/' . $selectedClass
		));
		?>
		<?php if(!empty($selectedItem)): ?>
		<ul class="legend">
			<li>Min Marks: <?php echo $minMarks; ?></li>
			<li>Max Marks: <?php echo $maxMarks; ?></li>
		</ul>
		<?php endif; ?>
</div>

<table class="table table-striped table-hover table-bordered">
	<thead>
		<tr>
			<th class="cell_id_no"><?php echo __('OpenEMIS ID'); ?></th>
			<th class=""><?php echo __('Student Name'); ?></th>
			<th class="cell_marks"><?php echo __('Marks'); ?></th>
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
				echo $this->Form->hidden($i . '.school_year_id', array('value' => $obj['InstitutionSiteClass']['school_year_id']));
				?>
				<td class="middle"><?php echo $obj['Student']['identification_no']; ?></td>
				<td class="middle"><?php echo sprintf('%s %s %s', $obj['Student']['first_name'], $obj['Student']['middle_name'], $obj['Student']['last_name']); ?></td>
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

<?php echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'assessmentsResults', $selectedYear, $assessmentId, $selectedClass, $selectedItem))); ?>
<?php
echo $this->Form->end();
$this->end();
?>
