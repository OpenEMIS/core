<?php
echo $this->Html->script('attendance.day', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Attendance') . ' - ' . __('Staff'));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => $model, 'dayview', $academicPeriodId, $weekId, $dayId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
echo $this->element("../InstitutionSites/$model/controls");

$formOptions = $this->FormUtility->getFormOptions(array('action' => $model, 'dayedit', $academicPeriodId, $weekId, $dayId));
echo $this->Form->create('InstitutionSiteProgramme', $formOptions);
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<?php 
				foreach ($header as $column): 
					echo '<th>'.__($column).'</th>';
				endforeach; 
				?>
			</tr>
		</thead>
		<tbody>
			<?php
			$todayIndex = date('Ymd');
			$count=0;
			foreach ($staffList as $staff):
				$staffObj = $staff['Staff'];
				$staffId = $staffObj['id'];
				$staffName = $this->Model->getName($staffObj);
				$additionalReasonOptionFieldData = array();

				echo $this->Form->hidden($model . '.' . $count . '.staff_id', array('value' => $staffId));
				?>
				<tr>
					<td><?php echo $staffObj['identification_no']; ?></td>
					<td><?php echo $staffName; ?></td>
					<?php
					$absenceType = 0;
					if (isset($absenceCheckList[$staffId][$selectedDateDigit])) {
						$absenceObj = $absenceCheckList[$staffId][$selectedDateDigit]['InstitutionSiteStaffAbsence'];
						$absenceReasonObj = $absenceCheckList[$staffId][$selectedDateDigit]['StaffAbsenceReason'];
						$additionalReasonOptionFieldData['value'] = $absenceReasonObj['id'];
						switch ($absenceObj['absence_type']) {
							case 'Excused':
								$absenceType = 1;
								break;
							case 'Unexcused':
								$absenceType = 2;
								break;
							default:
								$absenceType = 0;
								break;
						}
					} else {
						$absenceType = 0;
					}
					?>
					<td>
					<?php 
						echo $this->Form->input($model.'.'.$count.'.'.'staff_attendance_type', 
							array(
							'label' => false,
							'div' => false,
							'options' => $absenceTypeList,
							'class' => 'form-control',
							'value' => $absenceType,
							'onchange' => 'attendanceDay.changeAttendanceType(this)',
							'countId' => $count,
							'before' => false,
							'between' => false
							)
						)

					 ?>
					</td>
					<td>
						<div id=<?php echo "absenceTypeContainer".$count; ?>>
							<?php 
							echo ($absenceType==0)? '<div class="reasonVisible" style="display:none">':'<div class="reasonVisible">';
							 ?>
								<?php 
								echo $this->Form->input($model.'.'.$count.'.'.'staff_absence_reason_id', 
									array_merge(
										array(
										'label' => false,
										'div' => false,
										'options' => $absenceReasonList, 
										'class' => 'form-control',
										'before' => false,
										'between' => false
										),
										$additionalReasonOptionFieldData
									)
								);
								 ?>
								 <?php echo ($absenceType==0)? '<div class="reasonHidden">':'<div class="reasonHidden"  style="display:none">'; ?>
									-
								</div>
							</div>
						</div>
					</td>
				</tr>
			<?php
			$count++;
			endforeach; ?>
		</tbody>
	</table>
</div>

<div class="controls">
	<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link(__('Cancel'), array('action' => $model, 'dayview', $academicPeriodId, $weekId, $dayId), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php echo $this->Form->end(); ?>
<?php $this->end() ?>
