<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Attendance') . ' - ' . __('Staff'));

$this->start('contentActions');
echo $this->Html->link(__('Absence'), array('action' => $model, 'absence', $academicPeriodId, $weekId), array('class' => 'divider'));
echo $this->Html->link(__('Edit'), array('action' => $model, 'dayedit', $academicPeriodId, $weekId, $dayId), array('class' => 'divider'));
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
			foreach ($staffList as $staff):
				$staffObj = $staff['Staff'];
				$staffId = $staffObj['id'];
				$staffName = sprintf('%s %s %s', $staffObj['first_name'], $staffObj['middle_name'], $staffObj['last_name']);
				?>
				<tr>
					<td><?php echo $staffObj['identification_no']; ?></td>
					<td><?php echo $staffName; ?></td>
					<?php
					if (isset($absenceCheckList[$staffId][$selectedDateDigit])) {
						$absenceObj = $absenceCheckList[$staffId][$selectedDateDigit]['InstitutionSiteStaffAbsence'];
						$absenceReasonObj = $absenceCheckList[$staffId][$selectedDateDigit]['StaffAbsenceReason'];
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
