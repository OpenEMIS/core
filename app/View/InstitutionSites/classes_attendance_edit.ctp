<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="classesAttendanceEdit" class="content_wrapper">
	<?php
	echo $this->Form->create('ClassesAttendance', array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'InstitutionSites', 'action' => 'classesAttendanceEdit', $selectedYear)
	));
	?>
    <h1>
        <span><?php echo __('Edit Attendance'); ?></span>
		<?php
			echo $this->Html->link(__('View'), array('action' => 'classesAttendance', $classId), array('class' => 'divider'));
		?>
    </h1>
    <div class="legendWrapper"><?php echo $legend; ?></div>
    <?php echo $this->element('alert'); ?>

    <?php echo $this->Form->hidden('InstitutionSiteClassId', array('value' => $classId)); ?>
    <?php echo $this->Form->hidden('institutionSiteId', array('value' => $institutionSiteId)); ?>
    <?php echo $this->Form->hidden('schoolYearId', array('value' => $schoolYearId)); ?>
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
                    <?php $cnt = 0; ?>
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
                            <?php echo $this->Form->hidden('Attendance.'.$cnt.'.id', array('value' => $obj['StudentAttendance'][$attendanceTypeId]['id'])); ?>
                            <?php echo $this->Form->hidden('Attendance.'.$cnt.'.student_attendance_type_id', array('value' => $attendanceTypeId)); ?>
                            <?php echo $this->Form->hidden('Attendance.'.$cnt.'.student_id', array('value' => $obj['Student']['id'])); ?>
                            <?php $total += $obj['StudentAttendance'][$attendanceTypeId]['value']; ?>
                            <div class="table_cell cell_totals">
                                <div class="input_wrapper">
                                <?php
                                echo $this->Form->input('Attendance.'.$cnt.'.value', array(
                                    'type' => 'text',
                                    'computeType' => 'computeTotal',
                                    'value' => empty($obj['StudentAttendance'][$attendanceTypeId]['value']) ? 0 : $obj['StudentAttendance'][$attendanceTypeId]['value'],
                                    'maxlength' => 3,
                                    'onkeypress' => 'return utility.integerCheck(event)',
                                    'onkeyup' => 'jsTable.computeSubtotal(this)',
                                    'style' => 'text-align:right'
                                ));
                                ?>
                                </div>
                            </div>
                            <?php $cnt++; ?>
                        <?php endforeach; ?>
                        <div class="table_cell cell_subtotal cell_number"><?php echo $total; ?></div>
                    </div>
                    <?php } // end if ?>
                    <?php } // end for ?>
                    <?php } // end if ?>
                </div>
            </div>
        </fieldset>

        <?php } ?>
    </fieldset>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'classesAttendance', $classId), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>
