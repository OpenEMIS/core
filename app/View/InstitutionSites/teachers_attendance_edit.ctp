<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="classes" class="content_wrapper">
	<?php
	echo $this->Form->create('TeachersAttendance', array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'InstitutionSites', 'action' => 'teachersAttendanceEdit')
	));
	?>
    <h1>
        <span><?php echo __('Edit Attendance'); ?></span>
		<?php
			echo $this->Html->link(__('View'), array('action' => 'teachersAttendance', $selectedYear), array('class' => 'divider'));
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
    
    <div class="row year">
		<div class="labelattendance"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'options' => $years,
				'default' => $selectedYear,
				'onchange' => 'jsForm.change(this)',
				'url' => $this->params['controller'] . '/' . $this->action
			));
			?>
		</div>
	</div>
    
    <div class="row school_days">
		<div class="labelattendance"><?php echo __('School Days'); ?></div>
		<div class="value">
        <input type="text" class="default" value="<?php echo $schoolDays; ?>" disabled="disabled" />
        <input type="hidden" id="schoolDays" name="schoolDays" class="default" value="<?php echo $schoolDays; ?>"/>
        </div>
	</div>
    
    <div class="table full_width">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Total no of days attended'); ?></div>
			<div class="table_cell"><?php echo __('Total no of days absent'); ?></div>
             <div class="table_cell"><?php echo __('Total'); ?></div>
		</div>
		
        <?php
			$total = 0;
			if(!empty($data[0]['TeacherAttendance']['total_no_attend'])){
				$total += $data[0]['TeacherAttendance']['total_no_attend'];
			}
			if(!empty($data[0]['TeacherAttendance']['total_no_attend'])){
				$total += $data[0]['TeacherAttendance']['total_no_absence'];
			}
		?>
		
		<div class="table_body">
			<div class="table_row">
				<?php
				echo $this->Form->hidden('id', array('value' => empty($data[0]['TeacherAttendance']['id']) ? 0 : $data[0]['TeacherAttendance']['id']));
				echo $this->Form->hidden('teacher_id', array('value' => $teacherid));
				echo $this->Form->hidden('institution_site_id', array('value' => $institutionSiteId));
				?><div class="table_cell cell_totals">
					<div class="input_wrapper">
					<?php 
					echo $this->Form->input('total_no_attend', array(
						'type' => 'text',
						'computeType' => 'computeTotal',
						'value' => empty($data[0]['TeacherAttendance']['total_no_attend']) ? 0 : $data[0]['TeacherAttendance']['total_no_attend'],
						'maxlength' => 10,
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
					echo $this->Form->input('total_no_absence', array(
						'type' => 'text',
						'computeType' => 'computeTotal',
						'value' => empty($data[0]['TeacherAttendance']['total_no_absence']) ? 0 : $data[0]['TeacherAttendance']['total_no_absence'],
						'maxlength' => 10,
						'onkeypress' => 'return utility.integerCheck(event)',
						'onkeyup' => 'jsTable.computeSubtotal(this)',
						'style' => 'text-align:right'
					));
					?>
					</div>
				</div>
                <div class="table_cell cell_subtotal cell_number"><?php echo $total; ?></div>
			</div>
		</div>
	</div>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'teacherAttendance', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>
