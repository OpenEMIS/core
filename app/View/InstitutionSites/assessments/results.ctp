<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $educationGradeName . ' - ' . $assessmentName);

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'assessments'), array('class' => 'divider'));
if ($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'assessmentsResultsEdit', $selectedAcademicPeriod, $assessmentId, $selectedClass, $selectedItem), array('class' => 'divider'));
}
if ($_execute) {
	echo $this->Html->link($this->Label->get('general.export'), array('action' => 'assessmentsToExcel', $selectedAcademicPeriod, $assessmentId), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
?>

<div class="row page-controls">
	<div class="col-md-4">
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
	</div>
	<div class="col-md-4">
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
</div>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th class="cell_id_no"><?php echo __('OpenEMIS ID'); ?></th>
				<th><?php echo __('Student Name'); ?></th>
				<th class="cell_marks"><?php echo __('Marks'); ?></th>
				<th class="cell_grading"><?php echo __('Grading'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $obj) { ?>
				<tr>
					<td><?php echo $obj['SecurityUser']['openemis_no']; ?></td>
					<td><?php echo $this->Model->getName($obj['SecurityUser']); ?></td>
					<td class="center">
						<?php
						$marks = $obj[$model]['marks'];
						if (is_null($marks) || strlen(trim($marks)) == 0) {
							echo __('Not Recorded');
						} else {
							if ($marks < $obj['AssessmentItem']['min']) {
								echo sprintf('<span class="red">%s</span>', $marks);
							} else {
								echo $marks;
							}
						}
						?>
					</td>
					<td class="center"><?php echo $obj['AssessmentResultType']['name']; ?></td>
				</tr>
			<?php } ?>
		</tbody>
	</table>
</div>
<?php
echo $this->Form->end();
$this->end();
?>
