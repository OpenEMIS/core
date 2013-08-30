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
    <?php echo $this->element('alert'); ?>

    <?php echo $this->Form->hidden('institution_site_class_id', array('value' => empty($classId) ? 0 : $classId)); ?>
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
                    <?php $cnt = 0; ?>
                    <?php foreach($students as $obj) { ?>
                     <?php if($obj['InstitutionSiteClassGradeStudent']['institution_site_class_grade_id']==$id){ ?>
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
                        <?php echo $this->Form->hidden('Attendance.'.$cnt.'.id', array('value' => empty($obj['StudentAttendance']['id']) ? 0 : $obj['StudentAttendance']['id'])); ?>
                        <?php echo $this->Form->hidden('Attendance.'.$cnt.'.student_id', array('value' => empty($obj['Student']['id']) ? 0 : $obj['Student']['id'])); ?>
                        <?php echo $this->Form->hidden('Attendance.'.$cnt.'.institution_site_id', array('value' => empty($obj['InstitutionSiteClass']['institution_site_id']) ? 0 : $obj['InstitutionSiteClass']['institution_site_id'])); ?>
                        <div class="table_cell"><?php echo $obj['Student']['identification_no']; ?></div>
                        <div class="table_cell"><?php echo $obj['Student']['first_name'] . ' ' . $obj['Student']['last_name']; ?></div>
                        <div class="table_cell cell_totals">
                            <div class="input_wrapper">
                            <?php
                            echo $this->Form->input('Attendance.'.$cnt.'.total_no_attend', array(
                                'type' => 'text',
                                'computeType' => 'computeTotal',
                                'value' => empty($obj['StudentAttendance']['total_no_attend']) ? 0 : $obj['StudentAttendance']['total_no_attend'],
                                'maxlength' => 3,
                                'onkeypress' => 'return utility.integerCheck(event)',
                                'onkeyup' => 'jsTable.computeSubtotal(this)',
                                'style' => 'text-align:right'
                            ));
                            ?>
                            </div>
                        </div>
                        <div class="table_cell cell_totals">
                            <div class="input_wrapper">
                            <?php
                            echo $this->Form->input('Attendance.'.$cnt.'.total_no_absence', array(
                                'type' => 'text',
                                'computeType' => 'computeTotal',
                                'value' => empty($obj['StudentAttendance']['total_no_absence']) ? 0 : $obj['StudentAttendance']['total_no_absence'],
                                'maxlength' => 3,
                                'onkeypress' => 'return utility.integerCheck(event)',
                                'onkeyup' => 'jsTable.computeSubtotal(this)',
                                'style' => 'text-align:right'
                            ));
                            ?>
                            </div>
                        </div>
                        <div class="table_cell cell_subtotal cell_number"><?php echo $total; ?></div>
                    </div>
                    <?php } // end if ?>
                    <?php $cnt++; ?>
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
