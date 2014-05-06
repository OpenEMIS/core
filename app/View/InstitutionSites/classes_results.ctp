<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Results'));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'classesAssessments', $classId), array('class' => 'divider'));
if ($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'classesResultsEdit', $classId, $assessmentId, $selectedItem), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
?>

<div id="results" class="content_wrapper">

	<?php if (!empty($itemOptions) && !empty($data)) { ?>
		<div class="form-horizontal">
			<div class="form-group">
				<label class="control-label col-md-3"><?php echo __('Subject'); ?></label>
				<div class="col-md-4">
					<?php
					echo $this->Form->input('subject_id', array(
						'div' => false,
						'label' => false,
						'class' => 'form-control',
						'options' => $itemOptions,
						'default' => $selectedItem,
						'url' => sprintf('%s/%s/%d/%d', $this->params['controller'], $this->action, $classId, $assessmentId),
						'onchange' => 'jsForm.change(this)'
					));
					?>
				</div>
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
				<?php foreach ($data as $obj) { ?>
					<tr>
						<td class="table_cell"><?php echo $obj['Student']['identification_no']; ?></td>
						<td class="table_cell"><?php echo sprintf('%s %s %s', $obj['Student']['first_name'], $obj['Student']['middle_name'], $obj['Student']['last_name']); ?></td>
						<td class="table_cell center">
							<?php
							$marks = $obj['AssessmentItemResult']['marks'];
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
						<td class="table_cell center"><?php echo $obj['AssessmentResultType']['name']; ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	<?php } ?>
</div>
<?php $this->end(); ?>
