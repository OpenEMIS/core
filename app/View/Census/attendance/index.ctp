<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Attendance'));

$this->start('contentActions');
if ($_edit && $isEditable) {
	echo $this->Html->link(__('Edit'), array('action' => 'attendanceEdit', $selectedYear), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('census/year_options');
?>
<div id="attendance" class="dataDisplay">
	<div class="row school_days">
		<div class="label"><?php echo __('School Days'); ?></div>
		<div class="value"><input type="text" class="form-control" value="<?php echo $schoolDays; ?>" disabled="disabled" /></div>
	</div>
	<?php foreach ($data['programmeData'] as $programmeId => $programmeData) { ?>
		<fieldset class="section_group">
			<legend><?php echo $programmeData['programmeName']; ?></legend>
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th class="cell_grade"><?php echo __('Grade'); ?></th>
						<th><?php echo __('Days Absent') . '<br>' . __('(Male)'); ?></th>
						<th><?php echo __('Days Absent') . '<br>' . __('(Female)'); ?></th>
						<th><?php echo __('Days Attended') . '<br>' . __('(Male)'); ?></th>
						<th><?php echo __('Days Attended') . '<br>' . __('(Female)'); ?></th>
					</tr>
				</thead>

				<tbody>
					<?php 
					foreach ($programmeData['grades'] as $gradeId => $gradeName):
						?>
						<tr>
							<td><?php echo $gradeName; ?></td>
							<?php 
							$recordTagMale = "";
							$recordTagFemale = "";
							$maleValue = 0;
							$femaleValue = 0;
							foreach ($genderOptions AS $genderId => $genderName):
								if (isset($data['censusData'][$programmeId][$gradeId][$genderId])):
									$value = $data['censusData'][$programmeId][$gradeId][$genderId]['value'];
									
									foreach ($source_type as $k => $v):
										if ($data['censusData'][$programmeId][$gradeId][$genderId]['source'] == $v):
											if($genderName == 'Male'):
												$recordTagMale = "row_" . $k;
											else:
												$recordTagFemale = "row_" . $k;
											endif;
										endif;
									endforeach;
									
									if($genderName == 'Male'):
										$maleValue = $value;
									else:
										$femaleValue = $value;
									endif;
								endif;
							endforeach;
							
							$maleAttended = ($schoolDays - $maleValue) >= 0 ? ($schoolDays - $maleValue) : 0;
							$femaleAttended = ($schoolDays - $femaleValue) >= 0 ? ($schoolDays - $femaleValue) : 0;
							?>
							<td class="cell-number <?php echo $recordTagMale; ?>"><?php echo $maleValue; ?></td>
							<td class="cell-number <?php echo $recordTagFemale; ?>"><?php echo $femaleValue; ?></td>
							<td class="cell-number"><?php echo $maleAttended; ?></td>
							<td class="cell-number"><?php echo $femaleAttended; ?></td>
						</tr>
						<?php
					endforeach;
					?>
				</tbody>
			</table>
		</fieldset>
	<?php } ?>
</div>
<?php $this->end(); ?>