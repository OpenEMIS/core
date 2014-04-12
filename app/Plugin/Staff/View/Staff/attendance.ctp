<?php
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
