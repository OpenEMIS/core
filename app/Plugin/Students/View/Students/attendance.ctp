<?php
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
