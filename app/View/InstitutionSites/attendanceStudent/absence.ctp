<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site', false);
echo $this->Html->script('institution_attendance', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Absence') . ' - ' . __('Students'));

$this->start('contentActions');
echo $this->Html->link(__('Attendance'), array('action' => 'attendanceStudent'), array('class' => 'divider'));
echo $this->Html->link(__('Add'), array('action' => 'attendanceStudentAbsenceAdd', $classId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

echo $this->Form->create('InstitutionSiteStudentAbsence', array(
    'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
    'url' => array('controller' => $this->params['controller'], 'action' => 'attendanceStudentAbsence')
));
?>

<div id="classes" class=" institutionAttendance">
	<div class="topDropDownWrapper page-controls"  url="InstitutionSites/attendanceStudentAbsence">
		<?php 
			echo $this->Form->input('school_year_id', array('options' => $yearList, 'value' => $yearId, 'id' => 'schoolYearId', 'class' => 'form-control', 'onchange' => 'objInstitutionSite.filterAttendance(this)'));
			echo $this->Form->input('week_id', array('options' => $weekList, 'value' => $weekId, 'id' => 'weekId', 'class' => 'form-control', 'onchange' => 'objInstitutionSite.filterAttendance(this)'));
			echo $this->Form->input('class_id', array('options' => $classOptions, 'value' => $classId, 'id' => 'classId', 'class' => 'form-control', 'onchange' => 'objInstitutionSite.filterAttendance(this)'));
		?>
	</div>
	<div id="mainlist">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead url="InstitutionSites/attendanceStudentAbsence">
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
						
						$studentName = sprintf('%s %s %s %s', $student['first_name'], $student['middle_name'], $student['last_name'], $student['preferred_name']);
						
						$firstDateAbsent = $this->Utility->formatDate($arrItems['InstitutionSiteStudentAbsence']['first_date_absent'], null, false);
						$lastDateAbsent = $this->Utility->formatDate($arrItems['InstitutionSiteStudentAbsence']['last_date_absent'], null, false);
						$fullDayAbsent = $arrItems['InstitutionSiteStudentAbsence']['full_day_absent'];
						$startTimeAbsent = $arrItems['InstitutionSiteStudentAbsence']['start_time_absent'];
						$endTimeAbsent = $arrItems['InstitutionSiteStudentAbsence']['end_time_absent'];
						
						if($fullDayAbsent == 'Yes'){
							$dateStr = sprintf('%s (%s)', $firstDateAbsent, $this->Label->get('InstitutionSiteStudentAbsence.full_day_absent'));
						}else{
							$dateStr = sprintf('%s (%s - %s)', $firstDateAbsent, $startTimeAbsent, $endTimeAbsent);
						}
						?>
						<tr>
							<td><?php echo $dateStr; ?></td>
							<td><?php echo $this->Html->link($arrItems['Student']['identification_no'], array('action' => 'attendanceStudentAbsenceView', $id), array('escape' => false)); ?></td>

							<td><?php echo $studentName; ?></td>
							<td><?php echo $arrItems['InstitutionSiteStudentAbsence']['absence_type']; ?></td>
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