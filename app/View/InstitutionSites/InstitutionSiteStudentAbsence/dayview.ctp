<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Attendance') . ' - ' . __('Students'));

$this->start('contentActions');
echo $this->Html->link(__('Absence'), array('action' => $model, 'absence', $academicPeriodId, $sectionId, $weekId), array('class' => 'divider'));
echo $this->Html->link(__('Edit'), array('action' => $model, 'dayedit', $academicPeriodId, $sectionId, $weekId, $dayId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
echo $this->element("../InstitutionSites/$model/controls");
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<?php 
				$count = 0;
				foreach ($header as $column): 
					echo '<th>'.__($column).'</th>';
					$count++;
				endforeach; 
				?>
			</tr>
		</thead>
		<tbody>
			<?php
			$todayIndex = date('Ymd');
			foreach ($studentList as $student):

				$studentObj = $student['Student'];
				$studentId = $studentObj['id'];
				$studentName = $this->Model->getName($student['SecurityUser']);
				?>
				<tr>
					<td><?php echo $student['SecurityUser']['openemis_no']; ?></td>
					<td><?php echo $studentName; ?></td>
					<?php
					if (isset($absenceCheckList[$studentId][$selectedDateDigit])) {
						$absenceObj = $absenceCheckList[$studentId][$selectedDateDigit]['InstitutionSiteStudentAbsence'];
						$absenceReasonObj = $absenceCheckList[$studentId][$selectedDateDigit]['StudentAbsenceReason'];
						if ($absenceObj['full_day_absent'] !== 'Yes') {
							$startTimeAbsent = $absenceObj['start_time_absent'];
							$endTimeAbsent = $absenceObj['end_time_absent'];
							$timeStr = sprintf(__('Absent') . ' - ' . $absenceObj['absence_type']. ' (%s - %s)' , $startTimeAbsent, $endTimeAbsent);
							?>
							<td><?php echo $this->Html->link($timeStr, array('action' => $model, 'view', $absenceObj['id']), array('escape' => false)); ?></td>
							<?php
						} else {
							?>
							<td><?php echo $this->Html->link(__('Absent' . ' - ' . $absenceObj['absence_type'] . ' (full day)'), array('action' => $model, 'view', $absenceObj['id']), array('escape' => false)); ?></td>
							<?php
						}
						?>
						<td><?php echo $absenceReasonObj['name'] ?></td>
						<?php 
					} else {
						if ($selectedDateDigit <= $todayIndex) {
							?>
							<td class="present"><?php echo "&#10003"; ?></td>
							<?php
						} else {
							?>
							<td></td>
							<?php
						}
						?>
						<td>-</td>
						<?php 
					}
					?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<?php $this->end() ?>
