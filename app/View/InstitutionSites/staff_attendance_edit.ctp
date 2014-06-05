<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Edit Attendance'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'StaffAttendance', $selectedYear), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>

<div id="staffAttendanceEdit" class="content_wrapper dataDisplay">
	<?php
	echo $this->Form->create('StaffAttendance', array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'InstitutionSites', 'action' => 'staffAttendanceEdit')
	));
	?>
    <?php echo $this->Form->hidden('Attendance.staffId', array('value' => $staffid)); ?>
    <?php echo $this->Form->hidden('Attendance.institutionSiteId', array('value' => $institutionSiteId)); ?>
    
    <div class="row myyear">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('Attendance.school_year_id', array(
				'options' => $years,
				'class' => 'form-control',
				'default' => $selectedYear,
				'onchange' => 'jsForm.change(this)',
				'url' => $this->params['controller'] . '/' . $this->action
			));
			?>
		</div>
	</div>
    
    <div class="row school_days">
		<div class="label"><?php echo __('School Days'); ?></div>
		<div class="value">
        <input type="text" class="default form-control" value="<?php echo $schoolDays; ?>" disabled="disabled" />
        <input type="hidden" id="schoolDays" name="schoolDays" class="default" value="<?php echo $schoolDays; ?>"/>
        </div>
	</div>
    <div class="legendWrapper"><?php echo $legend; ?></div>
    <table class="table table-striped table-hover table-bordered">
		<thead class="table_head">
			<tr>
                    <?php foreach($attendanceTypes AS $attendanceType): ?>
                        <th class="table_cell"><?php echo __($attendanceType['StaffAttendanceType']['national_code']); ?></th>
                    <?php endforeach; ?>
             <th class="table_cell"><?php echo __('Total'); ?></th>
			 </tr>
		</thead>
		
        <?php
			$total = 0;
		?>
		
		<tbody class="table_body">
			<tr class="table_row">
                        <?php $cnt = 0; ?>
                        <?php foreach($attendanceTypes AS $attendanceType): ?>
                            <?php $attendanceTypeId = $attendanceType['StaffAttendanceType']['id']; ?>
                            <?php echo $this->Form->hidden('StaffAttendance.'.$cnt.'.id', array('value' => $data[$attendanceTypeId]['id'])); ?>
                            <?php echo $this->Form->hidden('StaffAttendance.'.$cnt.'.staff_attendance_type_id', array('value' => $attendanceTypeId)); ?>
                            <?php $total += $data[$attendanceTypeId]['value']; ?>
                            <td class="table_cell cell_totals">
                                <div class="input_wrapper">
                                <?php
                                echo $this->Form->input('StaffAttendance.'.$cnt.'.value', array(
                                    'type' => 'text',
                                    'computeType' => 'computeTotal',
                                    'value' => empty($data[$attendanceTypeId]['value']) ? 0 : $data[$attendanceTypeId]['value'],
                                    'maxlength' => 3,
                                    'onkeypress' => 'return utility.integerCheck(event)',
                                    'onkeyup' => 'jsTable.computeSubtotal(this)',
                                    'style' => 'text-align:right'
                                ));
                                ?>
                                </div>
                            </td>
                            <?php $cnt++; ?>
                        <?php endforeach; ?>
                <td class="table_cell cell_subtotal cell_number"><?php echo $total; ?></td>
			</tr>
		</tbody>
	</table>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'StaffAttendance', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>
