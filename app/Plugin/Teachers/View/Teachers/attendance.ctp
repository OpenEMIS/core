<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Teachers/css/teachers', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="attendance" class="content_wrapper">
    <h1>
        <span><?php echo __('Attendance'); ?></span>
    </h1>
    <?php echo $this->element('alert'); ?>

	<div class="row year">
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

	<div class="table full_width" style="margin-top: 10px;">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Total no of days attended'); ?></div>
			<div class="table_cell"><?php echo __('Total no of days absent'); ?></div>
            <div class="table_cell"><?php echo __('Total'); ?></div>
            <?php
				$total = 0;
				if(!empty($data[0]['TeacherAttendance']['total_no_attend'])){
					$total += $data[0]['TeacherAttendance']['total_no_attend'];
				}
				if(!empty($data[0]['TeacherAttendance']['total_no_absence'])){
					$total += $data[0]['TeacherAttendance']['total_no_absence'];
				}
			?>
		</div>

		<div class="table_body">
			<div class="table_row">
				<div class="table_cell cell_totals"><?php echo empty($data[0]['TeacherAttendance']['total_no_attend']) ? 0 : $data[0]['TeacherAttendance']['total_no_attend'] ?>
                </div>
				<div class="table_cell cell_totals"><?php echo empty($data[0]['TeacherAttendance']['total_no_absence']) ? 0 : $data[0]['TeacherAttendance']['total_no_absence'] ?>
                </div>
                <div class="table_cell cell_total cell_number"><?php echo $total; ?></div>
			</div>
		</div>
	</div>
</div>