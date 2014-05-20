<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site_classes', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Absence') . ' - ' . __('Students'));

$this->start('contentActions');
echo $this->Html->link(__('Attendance'), array('action' => 'attendanceStudent'), array('class' => 'divider'));
echo $this->Html->link(__('Add'), array('action' => 'attendanceStudentAbsenceAdd'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

?>

<div id="classes" class="content_wrapper">
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
	</div> <!-- mainlist end-->
</div>
<?php $this->end(); ?>