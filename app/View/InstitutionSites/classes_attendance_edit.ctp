<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Edit Attendance'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'classesAttendance', $classId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>

<div id="classesAttendanceEdit" class="content_wrapper">
	<?php
	echo $this->Form->create('ClassesAttendance', array(
		'inputDefaults' => array('label' => false, 'div' => false),
		'url' => array('controller' => 'InstitutionSites', 'action' => 'classesAttendanceEdit', $selectedYear)
	));
	?>
    <div class="legendWrapper"><?php echo $legend; ?></div>

	<?php echo $this->Form->hidden('InstitutionSiteClassId', array('value' => $classId)); ?>
	<?php echo $this->Form->hidden('institutionSiteId', array('value' => $institutionSiteId)); ?>
	<?php echo $this->Form->hidden('schoolYearId', array('value' => $schoolYearId)); ?>
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
							<?php $cnt = 0; ?>
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
											<?php echo $this->Form->hidden('Attendance.' . $cnt . '.id', array('value' => $obj['StudentAttendance'][$attendanceTypeId]['id'])); ?>
											<?php echo $this->Form->hidden('Attendance.' . $cnt . '.student_attendance_type_id', array('value' => $attendanceTypeId)); ?>
											<?php echo $this->Form->hidden('Attendance.' . $cnt . '.student_id', array('value' => $obj['Student']['id'])); ?>
											<?php $total += $obj['StudentAttendance'][$attendanceTypeId]['value']; ?>
											<td class="table_cell cell_totals">
												<?php
												echo $this->Form->input('Attendance.' . $cnt . '.value', array(
													'type' => 'text',
													'computeType' => 'computeTotal',
													'class' => 'form-control',
													'value' => empty($obj['StudentAttendance'][$attendanceTypeId]['value']) ? 0 : $obj['StudentAttendance'][$attendanceTypeId]['value'],
													'maxlength' => 3,
													'onkeypress' => 'return utility.integerCheck(event)',
													'onkeyup' => 'jsTable.computeSubtotal(this)',
													'style' => 'text-align:right'
												));
												?>
											</td>
											<?php $cnt++; ?>
										<?php endforeach; ?>
										<td class="table_cell cell_subtotal cell_number"><?php echo $total; ?></td>
									</tr>
								<?php } // end if ?>
							<?php } // end for ?>
						<?php } // end if ?>
					</tbody>
				</table>
			</fieldset>

		<?php } ?>
    </fieldset>

	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'classesAttendance', $classId), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>