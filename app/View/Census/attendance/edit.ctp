<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Attendance'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'attendance', $selectedYear), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

echo $this->Form->create('CensusAttendance', array(
	'inputDefaults' => array('label' => false, 'div' => false),
	'url' => array('controller' => 'Census', 'action' => 'attendanceEdit')
));

echo $this->element('census/year_options');
?>
<div id="attendance" class="dataDisplay edit">

	<div class="row school_days">
		<div class="label"><?php echo __('School Days'); ?></div>
		<div class="value"><input type="text" class="default" value="<?php echo $schoolDays; ?>" disabled="disabled" /></div>
	</div>
	<?php $index = 0; ?>
	<?php foreach ($data['programmeData'] as $programmeId => $programmeData) : ?>
		<fieldset class="section_group">
			<legend><?php echo $programmeData['programmeName']; ?></legend>
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th class="table_cell cell_grade"><?php echo __('Grade'); ?></th>
						<th class="table_cell"><?php echo __('Days Absent') . '<br>' . __('(Male)'); ?></th>
						<th class="table_cell"><?php echo __('Days Absent') . '<br>' . __('(Female)'); ?></th>
						<th class="table_cell"><?php echo __('Days Attended') . '<br>' . __('(Male)'); ?></th>
						<th class="table_cell"><?php echo __('Days Attended') . '<br>' . __('(Female)'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($programmeData['grades'] as $gradeId => $gradeName):
						?>
						<tr>
							<td><?php echo $gradeName; ?></td>
							<?php 
							$recordTag = "";
							$value = 0;
							$maleValue = 0;
							$femaleValue = 0;
							foreach ($genderOptions AS $genderId => $genderName):
								if (isset($data['censusData'][$programmeId][$gradeId][$genderId])):
									$value = $data['censusData'][$programmeId][$gradeId][$genderId]['value'];
									
									foreach ($source_type as $k => $v):
										if ($data['censusData'][$programmeId][$gradeId][$genderId]['source'] == $v):
											$recordTag = "row_" . $k;
										endif;
									endforeach;
									
									if($genderName == 'Male'):
										$maleValue = $value;
									else:
										$femaleValue = $value;
									endif;
								endif;
							?>
							<td>
								<div class="input_wrapper">
									<?php 
									echo $this->Form->hidden($index . '.id', array('value' => isset($data['censusData'][$programmeId][$gradeId][$genderId]['id']) ? $data['censusData'][$programmeId][$gradeId][$genderId]['id'] : 0));
									echo $this->Form->hidden($index . '.education_grade_id', array('value' => $gradeId));
									echo $this->Form->hidden($index . '.gender_id', array('value' => $genderId));
									
									echo $this->Form->input($index . '.value', array(
										'type' => 'text',
										'class' => $recordTag,
										'value' => $value,
										'maxlength' => 10,
										'onkeypress' => 'return utility.integerCheck(event)',
										'onkeyup' => $genderName == "Male" ? "Census.computeAttendance(this, 'male', $schoolDays)" : "Census.computeAttendance(this, 'female', $schoolDays)"
									));
									?>
								</div>
							</td>
							<?php 
							$index++;
							endforeach;
							
							$maleAttended = ($schoolDays - $maleValue) >= 0 ? ($schoolDays - $maleValue) : 0;
							$femaleAttended = ($schoolDays - $femaleValue) >= 0 ? ($schoolDays - $femaleValue) : 0;
							?>
							<td class="cell-number maleAttendance"><?php echo $maleAttended; ?></td>
							<td class="cell-number femaleAttendance"><?php echo $femaleAttended; ?></td>
						</tr>
						<?php
						endforeach; // end for
					?>
				</tbody>
			</table>
		</fieldset>
	<?php endforeach; ?>

	<?php if (!empty($data)) { ?>
		<div class="controls">
			<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
			<?php echo $this->Html->link(__('Cancel'), array('action' => 'attendance', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
		</div>
	<?php } ?>
	<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>