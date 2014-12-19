<?php
echo $this->Html->script('attendance.day', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Attendance') . ' - ' . __('Students'));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => $model, 'dayview', $yearId, $sectionId, $weekId, $dayId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
echo $this->element("../InstitutionSites/$model/controls");

$formOptions = $this->FormUtility->getFormOptions(array('action' => $model, 'dayedit', $yearId, $sectionId, $weekId, $dayId));
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
			foreach ($studentList as $student):
				$studentObj = $student['Student'];
				$studentId = $studentObj['id'];
				$studentName = sprintf('%s %s %s', $studentObj['first_name'], $studentObj['middle_name'], $studentObj['last_name']);
				$additionalReasonOptionFieldData = array();

				echo $this->Form->hidden($model . '.' . $count . '.student_id', array('value' => $studentId));
				echo $this->Form->hidden($model . '.' . $count . '.section_id', array('value' => $sectionId));
				?>
				<tr>
					<td><?php echo $studentObj['identification_no']; ?></td>
					<td><?php echo $studentName; ?></td>
					<?php
					$absenceType = 0;
					if (isset($absenceCheckList[$studentId][$selectedDateDigit])) {
						$absenceObj = $absenceCheckList[$studentId][$selectedDateDigit]['InstitutionSiteStudentAbsence'];
						$absenceReasonObj = $absenceCheckList[$studentId][$selectedDateDigit]['StudentAbsenceReason'];
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
						echo $this->Form->input($model.'.'.$count.'.'.'student_attendance_type', 
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
								echo $this->Form->input($model.'.'.$count.'.'.'student_absence_reason_id', 
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
	<?php echo $this->Html->link(__('Cancel'), array('action' => $model, 'dayview', $yearId, $sectionId, $weekId, $dayId), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php echo $this->Form->end(); ?>
<?php $this->end() ?>