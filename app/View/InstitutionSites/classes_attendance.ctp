<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Attendance'));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('controller' => 'InstitutionSites', 'action' => 'classesView', $classId), array('class' => 'divider'));
if ($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'classesAttendanceEdit', $selectedYear), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
?>

<div id="classesAttendance" class="content_wrapper">
    <div class="legendWrapper"><?php echo $legend; ?></div>

    <fieldset class="section_group">
        <legend><?php echo __('Students'); ?></legend>
		<?php foreach ($grades as $id => $name) { ?>

			<fieldset class="section_break">
				<legend><?php echo $name ?></legend>

				<table class="table table-striped table-hover table-bordered">
					<thead>
						<tr>
							<th class="table_cell cell_id_no"><?php echo __('OpenEMIS ID'); ?></th>
							<th class="table_cell"><?php echo __('Name'); ?></th>
							<?php foreach ($attendanceTypes AS $attendanceType): ?>
								<th class="table_cell"><?php echo __($attendanceType['StudentAttendanceType']['national_code']); ?></th>
							<?php endforeach; ?>
							<th class="table_cell"><?php echo __('Total'); ?></th>
						</tr>
					</thead>

					<tbody>
						<?php if (isset($students)) { ?>
							<?php foreach ($students as $obj) { ?>
								<?php if ($obj['InstitutionSiteClassGradeStudent']['institution_site_class_grade_id'] == $id) { ?>
									<?php
									$total = 0;
									?>
									<tr>
										<td class="table_cell"><?php echo $obj['Student']['identification_no']; ?></td>
										<td class="table_cell"><?php echo $obj['Student']['first_name'] . ' ' . $obj['Student']['middle_name'] . ' ' . $obj['Student']['last_name']; ?></td>
										<?php foreach ($attendanceTypes AS $attendanceType): ?>
											<?php $attendanceTypeId = $attendanceType['StudentAttendanceType']['id']; ?>
											<?php $attendanceValue = $obj['StudentAttendance'][$attendanceTypeId]['value']; ?>
											<?php $total += $attendanceValue; ?>
											<td class="table_cell cell_totals"><?php echo empty($attendanceValue) ? 0 : $attendanceValue ?></td>
										<?php endforeach; ?>
										<td class="table_cell cell_total cell_number"><?php echo $total; ?></td>
									</tr>
								<?php } // end if ?>
							<?php } // end for ?>
						<?php } // end if ?>
					</tbody>
				</table>
			</fieldset>
		<?php } ?>
    </fieldset>
</div>
<?php $this->end(); ?>