<?php /*
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Staff/css/staff', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="attendance" class="content_wrapper">
    <h1>
        <span><?php echo __('Attendance'); ?></span>
    </h1>
    <?php echo $this->element('alert'); ?>

	<div class="row myyear">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'label' => false,
				'div' => false,
				'options' => $years,
				'default' => $selectedYear,
				'onchange' => 'jsForm.change(this)',
				'url' => $this->params['controller'] . '/' . $this->action
			));
			?>
		</div>
	</div>

    <div class="row school_days">
		<div class="label"><?php echo __('School Days'); ?></div>
		<div class="value"><input type="text" class="default" value="<?php echo $schoolDays; ?>" disabled="disabled" /></div>
	</div>
<div class="legendWrapper"><?php echo $legend; ?></div>
	<div class="table full_width" style="margin-top: 10px;">
		<div class="table_head">
			<?php foreach($attendanceTypes AS $attendanceType): ?>
                            <div class="table_cell"><?php echo __($attendanceType['StaffAttendanceType']['national_code']); ?></div>
                        <?php endforeach; ?>
            <div class="table_cell"><?php echo __('Total'); ?></div>
            <?php
				$total = 0;
			?>
		</div>

		<div class="table_body">
			<div class="table_row">
                        <?php foreach($attendanceTypes AS $attendanceType): ?>
                            <?php $attendanceTypeId = $attendanceType['StaffAttendanceType']['id']; ?>
                            <?php $attendanceValue = isset($data[$attendanceTypeId]) ? $data[$attendanceTypeId] : 0; ?>
                            <?php $total += $attendanceValue; ?>
                            <div class="table_cell cell_totals"><?php echo $attendanceValue; ?></div>
                        <?php endforeach; ?>
                <div class="table_cell cell_total cell_number"><?php echo $total; ?></div>
			</div>
		</div>
	</div>
</div>
 * 
 */?>

<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentBody');
echo $this->Form->create('InstitutionSiteStaffAbsence', array(
    'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
    'url' => array('controller' => $this->params['controller'], 'action' => 'attendanceStaffAbsence')
));
?>
<div class="topDropDownWrapper page-controls" url="Staff/absence">
	<?php 
	echo $this->Form->input('school_year_id', array('options' => $yearList, 'value' => $yearId, 'id' => 'schoolYearId', 'class' => 'form-control', 'onchange' => 'jsForm.filterAbsenceByMonth(this)'));
	echo $this->Form->input('month_id', array('options' => $monthOptions, 'value' => $monthId, 'id' => 'monthId', 'class' => 'form-control', 'onchange' => 'jsForm.filterAbsenceByMonth(this)'));
	?>
</div>
<?php
if(isset($data)) { 

$tableHeaders = array(__('First Day'), __('Days'), __('Time'), __('Reason'), __('Type'));

$tableData = array();
foreach($data as $val) {
	$tempRow = array();
	$absenceObj = $val['InstitutionSiteStaffAbsence'];
	$firstDateFormatted = $this->Utility->formatDate($absenceObj['first_date_absent'], null, false);
	
	$stampFirstDateAbsent = strtotime($absenceObj['first_date_absent']);
	$stampLastDateAbsent = strtotime($absenceObj['last_date_absent']);
	
	if($absenceObj['full_day_absent'] == 'Yes'){
		if(!empty($absenceObj['last_date_absent']) && $stampLastDateAbsent > $stampFirstDateAbsent){
			$lastDateFormatted = $this->Utility->formatDate($absenceObj['last_date_absent'], null, false);
			$totalWeekdays = $this->Utility->getAbsenceDaysBySettings($absenceObj['first_date_absent'], $absenceObj['last_date_absent'], $settingWeekdays);
			$noOfDays = sprintf('%s (to %s)', $totalWeekdays, $lastDateFormatted);
		}else{
			$noOfDays = 1;
		}
		$timeStr = __('full day');
	}else{
		$noOfDays = 1;
		$timeStr = sprintf('%s - %s', $absenceObj['start_time_absent'], $absenceObj['end_time_absent']);
	}
	$reason = $val['StaffAbsenceReason']['name'];
	$type = $absenceObj['absence_type'];
	
	$tempRow[] = $firstDateFormatted;
	$tempRow[] = $noOfDays;
	$tempRow[] = $timeStr;
	$tempRow[] = $reason;
	$tempRow[] = $type;
	
	$tableData[] = $tempRow;
}

echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

}
$this->end();
?>