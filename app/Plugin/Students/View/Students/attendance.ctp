<?php /*
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="attendance" class="content_wrapper">
    <h1>
        <span><?php echo __('Attendance'); ?></span>
    </h1>

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

    <?php echo $this->element('alert'); ?>
    <div class="legendWrapper"><?php echo $legend; ?></div>

    <?php if(isset($data)) { ?>
    <div class="table full_width" style="margin-top: 10px;">
        <div class="table_head">
            <div class="table_cell"><?php echo __('Classes'); ?></div>
            <?php foreach($attendanceTypes AS $attendanceType): ?>
                <div class="table_cell"><?php echo __($attendanceType['StudentAttendanceType']['national_code']); ?></div>
            <?php endforeach; ?>
            <div class="table_cell"><?php echo __('Total'); ?></div>
        </div>
        <?php foreach($data as $val) { ?>
        <?php
            $total = 0;
            $classId = $val['classId'];
        ?>
        <div class="table_body">
            <div class="table_row">
                <div class="table_cell"><?php echo $val['className']; ?></div>
                <?php foreach($attendanceTypes AS $attendanceType): ?>
                            <?php $attendanceTypeId = $attendanceType['StudentAttendanceType']['id']; ?>
                            <?php $attendanceValue = $val['StudentAttendance'][$attendanceTypeId]; ?>
                            <?php $total += $attendanceValue; ?>
                            <div class="table_cell cell_totals"><?php echo empty($attendanceValue) ? 0 : $attendanceValue ?></div>
                <?php endforeach; ?>
                <div class="table_cell cell_total cell_number"><?php echo $total; ?></div>
            </div>
        </div>
        <?php } ?>
    </div>
    <?php } ?>
</div>
 * 
 */?>

<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentBody');
echo $this->Form->create('InstitutionSiteStudentAbsence', array(
    'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
    'url' => array('controller' => $this->params['controller'], 'action' => 'attendanceStudentAbsence')
));
?>
<div class="topDropDownWrapper page-controls" url="Students/attendance">
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
	$absenceObj = $val['InstitutionSiteStudentAbsence'];
	$firstDateFormatted = $this->Utility->formatDate($absenceObj['first_date_absent'], null, false);
	
	$stampFirstDateAbsent = strtotime($absenceObj['first_date_absent']);
	$stampLastDateAbsent = strtotime($absenceObj['last_date_absent']);
	
	if($absenceObj['full_day_absent'] == 'Yes'){
		if(!empty($absenceObj['last_date_absent']) && $stampLastDateAbsent > $stampFirstDateAbsent){
			$noOfDays = ceil(($stampLastDateAbsent - $stampFirstDateAbsent) / (60*60*24)) + 1;
		}else{
			$noOfDays = 1;
		}
		$timeStr = '';
	}else{
		$noOfDays = '';
		$timeStr = sprintf('%s - %s', $absenceObj['start_time_absent'], $absenceObj['end_time_absent']);
	}
	$reason = $val['StudentAbsenceReason']['name'];
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
