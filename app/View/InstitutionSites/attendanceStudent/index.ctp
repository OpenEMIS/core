<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site', false);
echo $this->Html->script('institution_attendance', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Attendance') . ' - ' . __('Students'));

$this->start('contentActions');
echo $this->Html->link(__('Absence'), array('action' => 'attendanceStudentAbsence'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

echo $this->Form->create('InstitutionSiteStudentAbsence', array(
	'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
	'url' => array('controller' => $this->params['controller'], 'action' => 'attendanceStudent')
));
?>

<div id="institutionStudentAttendance" class=" institutionAttendance">
	<div class="topDropDownWrapper page-controls" url="InstitutionSites/attendanceStudent">
		<?php
		echo $this->Form->input('school_year_id', array('options' => $yearList, 'value' => $yearId, 'id' => 'schoolYearId', 'class' => 'form-control', 'onchange' => 'objInstitutionSite.filterStudentAttendance(this)'));
		echo $this->Form->input('week_id', array('options' => $weekList, 'value' => $weekId, 'id' => 'weekId', 'class' => 'form-control', 'onchange' => 'objInstitutionSite.filterStudentAttendance(this)'));
		echo $this->Form->input('class_id', array('options' => $classOptions, 'value' => $classId, 'id' => 'classId', 'class' => 'form-control', 'onchange' => 'objInstitutionSite.filterStudentAttendance(this)'));
		?>
	</div>
	<div id="mainlist">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead url="InstitutionSites/attendanceStudentAbsence">
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
								if(isset($absenceCheckList[$studentId][$index])){
									$absenceObj = $absenceCheckList[$studentId][$index]['InstitutionSiteStudentAbsence'];
									if($absenceObj['full_day_absent'] !== 'Yes'){
										$startTimeAbsent = $absenceObj['start_time_absent'];
										$endTimeAbsent = $absenceObj['end_time_absent'];
										$timeStr = sprintf(__('absent') . ' (%s - %s)', $startTimeAbsent, $endTimeAbsent);
										?>
											<td><?php echo $this->Html->link($timeStr, array('action' => 'attendanceStudentAbsenceView', $absenceObj['id']), array('escape' => false)); ?></td>
										<?php 
									}else{
										?>
											<td><?php echo $this->Html->link(__('absent (full day)'), array('action' => 'attendanceStudentAbsenceView', $absenceObj['id']), array('escape' => false)); ?></td>
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