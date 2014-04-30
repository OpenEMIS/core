<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="classesAttendance" class="content_wrapper">
    <h1>
        <span><?php echo __('Attendance'); ?></span>
        <?php
        echo $this->Html->link(__('Back'), array('controller' => 'InstitutionSites', 'action' => 'classesView', $classId), array('class' => 'divider'));
        if($_edit) {
            echo $this->Html->link(__('Edit'), array('action' => 'classesAttendanceEdit', $selectedYear), array('class' => 'divider'));
        }
        ?>
    </h1>
    <div class="legendWrapper"><?php echo $legend; ?></div>
    <?php echo $this->element('alert'); ?>

    <fieldset class="section_group">
        <legend><?php echo __('Students'); ?></legend>
        <?php foreach($grades as $id => $name) { ?>

        <fieldset class="section_break">
            <legend><?php echo $name ?></legend>

            <div class="table">
                <div class="table_head">
                    <div class="table_cell cell_id_no"><?php echo __('OpenEMIS ID'); ?></div>
                    <div class="table_cell"><?php echo __('Name'); ?></div>
                    <?php foreach($attendanceTypes AS $attendanceType): ?>
                        <div class="table_cell"><?php echo __($attendanceType['StudentAttendanceType']['national_code']); ?></div>
                    <?php endforeach; ?>
                    <div class="table_cell"><?php echo __('Total'); ?></div>
                </div>

                <div class="table_body">
                    <?php if(isset($students)) { ?>
                    <?php foreach($students as $obj) { ?>
                    <?php if($obj['InstitutionSiteClassGradeStudent']['institution_site_class_grade_id']==$id){ ?>
                    <?php
                        $total = 0;
                    ?>
                    <div class="table_row">
                        <div class="table_cell"><?php echo $obj['Student']['identification_no']; ?></div>
                        <div class="table_cell"><?php echo $obj['Student']['first_name'] . ' ' . $obj['Student']['middle_name'] . ' ' . $obj['Student']['last_name']; ?></div>
                        <?php foreach($attendanceTypes AS $attendanceType): ?>
                            <?php $attendanceTypeId = $attendanceType['StudentAttendanceType']['id']; ?>
                            <?php $attendanceValue = $obj['StudentAttendance'][$attendanceTypeId]['value']; ?>
                            <?php $total += $attendanceValue; ?>
                            <div class="table_cell cell_totals"><?php echo empty($attendanceValue) ? 0 : $attendanceValue ?></div>
                        <?php endforeach; ?>
                        <div class="table_cell cell_total cell_number"><?php echo $total; ?></div>
                    </div>
                    <?php } // end if ?>
                    <?php } // end for ?>
                    <?php } // end if ?>
                </div>
            </div>
        </fieldset>
        <?php } ?>
    </fieldset>
</div>
