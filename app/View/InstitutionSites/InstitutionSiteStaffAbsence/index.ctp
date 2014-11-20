<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site', false);
echo $this->Html->script('institution_attendance', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Attendance') . ' - ' . __('Staff'));

$this->start('contentActions');
echo $this->Html->link(__('Absence'), array('action' => $model, 'absence'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

echo $this->Form->create('InstitutionSiteStaffAbsence', array(
	'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
	'url' => array('controller' => $this->params['controller'], 'action' => 'attendanceStaff')
));
?>

<div id="institutionStaffAttendance" class=" institutionAttendance">
	<?php echo $this->element("../InstitutionSites/$model/controls"); ?>
	<div id="mainlist">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead url="InstitutionSites/attendanceStaffAbsence">
					<tr>
						<?php foreach ($header as $column): ?>
							<th><?php echo __($column); ?></th>
						<?php endforeach; ?>
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
							foreach ($weekDayIndex as $index):
								if(isset($absenceCheckList[$staffId][$index])){
									$absenceObj = $absenceCheckList[$staffId][$index]['InstitutionSiteStaffAbsence'];
									if($absenceObj['full_day_absent'] !== 'Yes'){
										$startTimeAbsent = $absenceObj['start_time_absent'];
										$endTimeAbsent = $absenceObj['end_time_absent'];
										$timeStr = sprintf(__('absent') . ' (%s - %s)', $startTimeAbsent, $endTimeAbsent);
										?>
											<td><?php echo $this->Html->link($timeStr, array('action' => $model, 'view', $absenceObj['id']), array('escape' => false)); ?></td>
										<?php 
									}else{
										?>
											<td><?php echo $this->Html->link(__('absent (full day)'), array('action' => $model, 'view', $absenceObj['id']), array('escape' => false)); ?></td>
										<?php 
									}
								}else{
									if($index <= $todayIndex){
										?>
											<td class="present"><?php echo "&#10003"; ?></td>
										<?php 
									}else{
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
	</div> 
</div>
<?php
echo $this->Form->end();
$this->end();
?>