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
						<?php
						foreach ($header as $column):
							?>
							<th><?php echo __($column); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($data as $arrItems):
						?>
						<tr>
							<td><?php echo ''; ?></td>
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