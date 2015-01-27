<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Classes'));

$this->start('contentActions');
if ($_edit && $isEditable) {
	echo $this->Html->link(__('Edit'), array('action' => 'classesEdit', $selectedAcademicPeriod), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('census/academic_period_options');
?>

<div class="table-responsive">

	<?php if ($displayContent) { ?>
		<fieldset class="section_group">
			<legend><?php echo __('Single Grade Classes Only'); ?></legend>

			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th><?php echo __('Programme'); ?></th>
						<th class="cell_grade"><?php echo __('Grade'); ?></th>
						<th class="cell_classes"><?php echo __('Classes'); ?></th>
						<th class="cell_classes"><?php echo __('Seats'); ?></th>
					</tr>
				</thead>
				<?php 
				$totalClasses = 0;
				$totalSeats = 0;
				if (!empty($singleGradeData)) : ?>
				<tbody>
					<?php
					foreach ($singleGradeData as $name => $programme) {
						foreach ($programme['education_grades'] as $gradeId => $grade) {
							$totalClasses += $grade['classes'];
							$totalSeats += $grade['seats'];
							$record_tag = "";
							foreach ($source_type as $k => $v) {
								if ($grade['source'] == $v) {
									$record_tag = "row_" . $k;
								}
							}
							?>
							<tr>
								<td class="<?php echo $record_tag; ?>"><?php echo $name; ?></td>
								<td class="<?php echo $record_tag; ?>"><?php echo $grade['name']['gradeName']; ?></td>
								<td class="cell-number <?php echo $record_tag; ?>"><?php echo $grade['classes']; ?></td>
								<td class="cell-number <?php echo $record_tag; ?>"><?php echo $grade['seats']; ?></td>
							</tr>

							<?php
						}
					}
					?>
				</tbody>
				<?php endif; ?>

				<tfoot>
					<tr>
						<td class="cell_label cell-number" colspan="2"><?php echo __('Total'); ?></td>
						<td class="cell_value cell-number"><?php echo $totalClasses; ?></td>
						<td class="cell_value cell-number"><?php echo $totalSeats; ?></td>
					</tr>
				</tfoot>
			</table>
		</fieldset>

		<fieldset class="section_group multi">
			<legend><?php echo __('Multi Grade Classes'); ?></legend>

			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th><?php echo __('Programme'); ?></th>
						<th class="cell_grade"><?php echo __('Grade'); ?></th>
						<th class="cell_classes"><?php echo __('Classes'); ?></th>
						<th class="cell_classes"><?php echo __('Seats'); ?></th>
					</tr>
				</thead>

				<?php
				$totalClasses = 0;
				$totalSeats = 0;
				if (!empty($multiGradeData)) {
					?>
					<tbody>
						<?php foreach ($multiGradeData as $obj) { ?>
							<tr>

								<?php
								$totalClasses += $obj['classes'];
								$totalSeats += $obj['seats'];
								$record_tag = "";
								foreach ($source_type as $k => $v) {
									if ($obj['source'] == $v) {
										$record_tag = "row_" . $k;
									}
								}
								?>
								<td class="<?php echo $record_tag; ?>">
									<?php foreach ($obj['programmes'] as $programmeId => $programmeName) { ?>
										<div class="table_cell_row"><?php echo $programmeName; ?></div>
									<?php } ?>
								</td>

								<td class="<?php echo $record_tag; ?>">
									<?php foreach ($obj['grades'] as $gradeId => $gradeName) { ?>
										<div class="table_cell_row"><?php echo $gradeName; ?></div>
									<?php } ?>
								</td>

								<td class="cell-number"><?php echo $obj['classes']; ?></td>
								<td class="cell-number"><?php echo $obj['seats']; ?></td>
							</tr>
						<?php } // end for (multigrade) ?>
					</tbody>
				<?php } // end if empty(multigrade) ?>
				<tfoot>
					<tr>
						<td class="cell_label cell-number" colspan="2"><?php echo __('Total'); ?></td>
						<td class="cell_value cell-number"><?php echo $totalClasses; ?></td>
						<td class="cell_value cell-number"><?php echo $totalSeats; ?></td>
					</tr>
				</tfoot>
			</table>
		</fieldset>

	<?php } ?>
</div>

<?php $this->end(); ?>