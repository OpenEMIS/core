<?php
echo $this->Html->script('institution_site', false);
echo $this->Html->script('institution_attendance', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Absence') . ' - ' . __('Students'));

$this->start('contentActions');
echo $this->Html->link(__('Attendance'), array('action' => $model, 'index' , $yearId, $classId, $weekId), array('class' => 'divider'));
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => $model, 'add', $classId), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element("../InstitutionSites/$model/controls");
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo __('Date'); ?></th>
				<th><?php echo __('ID'); ?></th>
				<th><?php echo __('Name'); ?></th>
				<th><?php echo __('Type'); ?></th>
			</tr>
		</thead>
		
		<tbody>
			<?php
			foreach ($data as $arrItems):
				$id = $arrItems['InstitutionSiteStudentAbsence']['id'];
				$student = $arrItems['Student'];

				$studentName = sprintf('%s %s %s', $student['first_name'], $student['middle_name'], $student['last_name']);

				$firstDateAbsentOriginal = $arrItems['InstitutionSiteStudentAbsence']['first_date_absent'];
				$lastDateAbsentOriginal = $arrItems['InstitutionSiteStudentAbsence']['last_date_absent'];
				$firstDateAbsent = $this->Utility->formatDate($firstDateAbsentOriginal, null, false);
				$lastDateAbsent = $this->Utility->formatDate($lastDateAbsentOriginal, null, false);
				$fullDayAbsent = $arrItems['InstitutionSiteStudentAbsence']['full_day_absent'];
				$startTimeAbsent = $arrItems['InstitutionSiteStudentAbsence']['start_time_absent'];
				$endTimeAbsent = $arrItems['InstitutionSiteStudentAbsence']['end_time_absent'];

				if ($fullDayAbsent == 'Yes') {
					if (!empty($lastDateAbsentOriginal) && strtotime($lastDateAbsentOriginal) > strtotime($firstDateAbsentOriginal)) {
						$dateStr = sprintf('%s - %s (%s)', $firstDateAbsent, $lastDateAbsent, __('full day'));
					} else {
						$dateStr = sprintf('%s (%s)', $firstDateAbsent, __('full day'));
					}
				} else {
					$dateStr = sprintf('%s (%s - %s)', $firstDateAbsent, $startTimeAbsent, $endTimeAbsent);
				}
				?>
				<tr>
					<td><?php echo $dateStr; ?></td>
					<td><?php echo $this->Html->link($arrItems['Student']['identification_no'], array('action' => $model, 'view', $id), array('escape' => false)); ?></td>
					<td><?php echo $studentName; ?></td>
					<td><?php echo $arrItems['InstitutionSiteStudentAbsence']['absence_type']; ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>	
<?php $this->end() ?>
