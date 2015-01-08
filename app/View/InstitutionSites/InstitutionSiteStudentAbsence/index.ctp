<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Attendance') . ' - ' . __('Students'));

$this->start('contentActions');
	echo $this->Html->link(__('Absence'), array('action' => $model, 'absence', $yearId, $sectionId, $weekId), array('class' => 'divider'));
	if ($_execute) {
		echo $this->Html->link($this->Label->get('general.export'), array('action' => 'InstitutionSiteStudentAttendance', 'excel', $yearId, $sectionId), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
echo $this->element("../InstitutionSites/$model/controls");
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<?php foreach ($header as $column): ?>
					<th><?php echo __($column); ?></th>
				<?php endforeach; ?>
			</tr>
		</thead>
		
		<tbody>
			<?php
			$todayIndex = date('Ymd');
			foreach ($studentList as $student):
				$studentObj = $student['Student'];
				$studentId = $studentObj['id'];
				$studentName = sprintf('%s %s %s', $studentObj['first_name'], $studentObj['middle_name'], $studentObj['last_name']);
				?>
				<tr>
					<td><?php echo $studentObj['identification_no']; ?></td>
					<td><?php echo $studentName; ?></td>
					<?php
					foreach ($weekDayIndex as $index):
						if (isset($absenceCheckList[$studentId][$index])) {
							$absenceObj = $absenceCheckList[$studentId][$index]['InstitutionSiteStudentAbsence'];
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
						} else {
							if ($index <= $todayIndex) {
								?>
								<td class="present"><?php echo "&#10003"; ?></td>
								<?php
							} else {
								?>
								<td></td>
								<?php
							}
						}
						?>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<?php $this->end() ?>
