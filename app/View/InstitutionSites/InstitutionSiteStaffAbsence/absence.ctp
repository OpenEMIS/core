<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site', false);
echo $this->Html->script('institution_attendance', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Absence') . ' - ' . __('Staff'));

$this->start('contentActions');
echo $this->Html->link(__('Attendance'), array('action' => $model, 'index' , $academicPeriodId, $weekId), array('class' => 'divider'));
if ($_add) {
	echo $this->Html->link(__('Add'), array('action' => $model, 'add'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');

echo $this->Form->create('InstitutionSiteStaffAbsence', array(
	'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
	'url' => array('controller' => $this->params['controller'], 'action' => 'attendanceStaffAbsence')
));
?>

<div id="classes" class=" institutionAttendance">
	<?php echo $this->element("../InstitutionSites/$model/controls"); ?>
	<div id="mainlist">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead url="InstitutionSites/attendanceStaffAbsence">
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
						$id = $arrItems['InstitutionSiteStaffAbsence']['id'];
						$staff = $arrItems['Staff'];

						$staffName = sprintf('%s %s %s %s', $staff['first_name'], $staff['middle_name'], $staff['last_name'], $staff['preferred_name']);

						$firstDateAbsentOriginal = $arrItems['InstitutionSiteStaffAbsence']['first_date_absent'];
						$lastDateAbsentOriginal = $arrItems['InstitutionSiteStaffAbsence']['last_date_absent'];
						$firstDateAbsent = $this->Utility->formatDate($firstDateAbsentOriginal, null, false);
						$lastDateAbsent = $this->Utility->formatDate($lastDateAbsentOriginal, null, false);
						$fullDayAbsent = $arrItems['InstitutionSiteStaffAbsence']['full_day_absent'];
						$startTimeAbsent = $arrItems['InstitutionSiteStaffAbsence']['start_time_absent'];
						$endTimeAbsent = $arrItems['InstitutionSiteStaffAbsence']['end_time_absent'];

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
							<td><?php echo $this->Html->link($arrItems['Staff']['identification_no'], array('action' => $model, 'view', $id), array('escape' => false)); ?></td>

							<td><?php echo $staffName; ?></td>
							<td><?php echo $arrItems['InstitutionSiteStaffAbsence']['absence_type']; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div> 
</div>
<?php
echo $this->Form->end();
$this->end();
?>