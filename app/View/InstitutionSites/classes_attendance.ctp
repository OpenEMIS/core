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
    <?php echo $this->element('alert'); ?>

    <fieldset class="section_group">
        <legend><?php echo __('Students'); ?></legend>
        <?php foreach($grades as $id => $name) { ?>

        <fieldset class="section_break">
            <legend><?php echo $name ?></legend>

            <div class="table">
                <div class="table_head">
                    <div class="table_cell cell_id_no"><?php echo __('Identification No'); ?></div>
                    <div class="table_cell"><?php echo __('Name'); ?></div>
                    <div class="table_cell cell_id_no"><?php echo __('Attended'); ?></div>
                    <div class="table_cell cell_id_no"><?php echo __('Absent'); ?></div>
                    <div class="table_cell cell_id_no"><?php echo __('Total'); ?></div>
                </div>

                <div class="table_body">
                    <?php if(isset($students)) { ?>
                    <?php foreach($students as $obj) { ?>
                    <?php
                        $total = 0;
                        if(!empty($obj['StudentAttendance']['total_no_attend'])){
                            $total += $obj['StudentAttendance']['total_no_attend'];
                        }
                        if(!empty($obj['StudentAttendance']['total_no_absence'])){
                            $total += $obj['StudentAttendance']['total_no_absence'];
                        }
                    ?>
                    <div class="table_row">
                        <div class="table_cell"><?php echo $obj['Student']['identification_no']; ?></div>
                        <div class="table_cell"><?php echo $obj['Student']['first_name'] . ' ' . $obj['Student']['last_name']; ?></div>
                        <div class="table_cell cell_totals"><?php echo empty($obj['StudentAttendance']['total_no_attend']) ? 0 : $obj['StudentAttendance']['total_no_attend'] ?></div>
                        <div class="table_cell cell_totals"><?php echo empty($obj['StudentAttendance']['total_no_absence']) ? 0 : $obj['StudentAttendance']['total_no_absence'] ?></div>
                        <div class="table_cell cell_total cell_number"><?php echo $total; ?></div>
                    </div>
                    <?php } // end for ?>
                    <?php } // end if ?>
                </div>
            </div>
        </fieldset>

        <?php } ?>
    </fieldset>
</div>
