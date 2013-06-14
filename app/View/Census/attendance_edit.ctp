<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="attendance" class="content_wrapper edit">
	<?php
	echo $this->Form->create('CensusAttendance', array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Census', 'action' => 'attendanceEdit')
	));
	?>
	<h1>
		<span><?php echo __('Attendance'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'attendance', $selectedYear), array('class' => 'divider')); ?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="row">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'options' => $years,
				'default' => $selectedYear,
				'onchange' => 'Census.navigateYear(this)',
				'url' => 'Census/' . $this->action
			));
			?>
		</div>
		<div class="row_item_legend">
		<ul class="legend">
			<li><span class="dataentry"></span><?php echo __('Data Entry'); ?></li>
			<li><span class="external"></span><?php echo __('External'); ?></li>
			<li><span class="estimate"></span><?php echo __('Estimate'); ?></li>
		</ul>
		</div>
	</div>
	
	<div class="row school_days">
		<div class="label"><?php echo __('School Days'); ?></div>
		<div class="value"><input type="text" class="default" value="<?php echo $schoolDays; ?>" disabled="disabled" /></div>
	</div>
	
	<?php $index = 0; ?>
	<?php foreach($data as $obj) { ?>
	<fieldset class="section_group">
		<legend><?php echo $obj['name']; ?></legend>
		
		<div class="table full_width">
			<div class="table_head">
				<div class="table_cell cell_grade"><?php echo __('Grade'); ?></div>
				<div class="table_cell"><?php echo __('Days Attended') . '<br>' . __('(Male)'); ?></div>
				<div class="table_cell"><?php echo __('Days Attended') . '<br>' . __('(Female)'); ?></div>
				<div class="table_cell"><?php echo __('Days Absent') . '<br>' . __('(Male)'); ?></div>
				<div class="table_cell"><?php echo __('Days Absent') . '<br>' . __('(Female)'); ?></div>
				<div class="table_cell"><?php echo __('Total'); ?></div>
			</div>
			
			<div class="table_body">
				<?php 
				$total = 0;
				
				foreach($obj['data'] as $record) {
					$subtotal = $record['attended_male'] + $record['attended_female'] + $record['absent_male'] + $record['absent_female'];
					$total += $subtotal;
					$record_tag="";
					switch ($record['source']) {
						case 1:
							$record_tag.="row_external";break;
						case 2:
							$record_tag.="row_estimate";break;
					}
				?>
				<div class="table_row">
					<?php echo $this->Form->hidden($index . '.id', array('value' => $record['id'])); ?>
					<?php echo $this->Form->hidden($index . '.education_grade_id', array('value' => $record['education_grade_id'])); ?>
					<div class="table_cell <?php echo $record_tag; ?>"><?php echo $record['education_grade_name']; ?></div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php 
						echo $this->Form->input($index . '.attended_male', array(
							'type' => 'text',
							'class' => 'computeTotal ' . $record_tag,
							'value' => is_null($record['attended_male']) ? 0 : $record['attended_male'],
							'maxlength' => 10,
							'onkeypress' => 'return utility.integerCheck(event)',
							'onkeyup' => 'Census.computeTotal(this)'
						));
						?>
						</div>
					</div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php 
						echo $this->Form->input($index . '.attended_female', array(
							'type' => 'text',
							'class' => 'computeTotal ' . $record_tag,
							'value' => is_null($record['attended_female']) ? 0 : $record['attended_female'],
							'maxlength' => 10,
							'onkeypress' => 'return utility.integerCheck(event)',
							'onkeyup' => 'Census.computeTotal(this)'
						));
						?>
						</div>
					</div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php 
						echo $this->Form->input($index . '.absent_male', array(
							'type' => 'text',
							'class' => 'computeTotal ' . $record_tag,
							'value' => is_null($record['absent_male']) ? 0 : $record['absent_male'],
							'maxlength' => 10,
							'onkeypress' => 'return utility.integerCheck(event)',
							'onkeyup' => 'Census.computeTotal(this)'
						));
						?>
						</div>
					</div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php 
						echo $this->Form->input($index . '.absent_female', array(
							'type' => 'text',
							'class' => 'computeTotal ' . $record_tag,
							'value' => is_null($record['absent_female']) ? 0 : $record['absent_female'],
							'maxlength' => 10,
							'onkeypress' => 'return utility.integerCheck(event)',
							'onkeyup' => 'Census.computeTotal(this)'
						));
						?>
						</div>
					</div>
					<div class="table_cell cell_total cell_number <?php echo $record_tag; ?>"><?php echo $subtotal; ?></div>
				</div>
				<?php 
					$index++;
				} // end for
				?>
			</div>
			<div class="table_foot">
				<div class="table_cell"></div>
				<div class="table_cell"></div>
				<div class="table_cell"></div>
				<div class="table_cell"></div>
				<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
				<div class="table_cell cell_value cell_number"><?php echo $total; ?></div>
			</div>
		</div>
	</fieldset>
	<?php } ?>
	
	<?php if(!empty($data)) { ?>
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'attendance', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php } ?>
	<?php echo $this->Form->end(); ?>
</div>