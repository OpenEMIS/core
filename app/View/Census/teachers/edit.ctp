<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('census_teachers', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Teachers'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'teachers', $selectedYear), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

echo $this->Form->create('CensusTeacher', array(
	'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
	'url' => array('controller' => 'Census', 'action' => 'teachersEdit')
));
echo $this->element('census/year_options');
?>

<div id="teachers" class="content_wrapper edit page-controls">

	<?php if ($displayContent) { ?>
		<fieldset class="section_group">
			<legend><?php echo __('Full Time Equivalent Teachers'); ?></legend>

			<table class="table table-striped table-hover table-bordered">
				<thead>
					<?php echo $this->Html->tableHeaders(array(__('Education Level'), __('Male'), __('Female'), __('Total'))); ?>
				</thead>

				<?php
				$total = 0;
				?>
				<tbody>
					<?php
					$index = 0;
					$fieldName = 'data[CensusTeacherFte][%d][%s]';

					foreach ($eduLevelOptions as $eduLevelId => $eduLevelName):
						$subTotal = 0;
						?>
						<tr>
							<td><?php echo $eduLevelName; ?></td>
							<?php
							foreach ($genderOptions AS $genderId => $genderName):
								?>
								<td>
									<div class="input_wrapper">
										<?php
										echo $this->Form->hidden('id', array('name' => sprintf($fieldName, $index, 'id'), 'value' => isset($fte[$eduLevelId][$genderId]['censusId']) ? $fte[$eduLevelId][$genderId]['censusId'] : 0));
										echo $this->Form->hidden('education_level_id', array('name' => sprintf($fieldName, $index, 'education_level_id'), 'value' => $eduLevelId));
										echo $this->Form->hidden('gender_id', array('name' => sprintf($fieldName, $index, 'gender_id'), 'value' => $genderId));

										$record_tag = '';
										foreach ($source_type as $k => $v):
											if (isset($fte[$eduLevelId][$genderId]['source']) && $fte[$eduLevelId][$genderId]['source'] == $v) {
												$record_tag = "row_" . $k;
											}
										endforeach;

										if (!empty($fte[$eduLevelId][$genderId]['value'])) {
											$value = $fte[$eduLevelId][$genderId]['value'];
											$subTotal += $value;
										} else {
											$value = 0;
										}

										echo $this->Form->input('value', array(
											'type' => 'text',
											'class' => $record_tag,
											'name' => sprintf($fieldName, $index, 'value'),
											'computeType' => 'cell_value',
											'value' => $value,
											'maxlength' => 7,
											'onkeypress' => 'return CensusTeachers.decimalCheck(event,1)',
											'onkeyup' => 'CensusTeachers.computeSubtotal(this)',
											'onblur' => 'CensusTeachers.clearBlank(this)'
										));
										?>
									</div>
								</td>
								<?php
								$index++;
							endforeach;
							?>
							<td class=" cell_number cell_subtotal"><?php echo $subTotal; ?></td>
						</tr>
						<?php
						$total += $subTotal;
					endforeach;
					?>
				</tbody>
				<tfoot>
					<tr>
						<td></td>
						<td></td>
						<td><?php echo __('Total'); ?></td>
						<td class="cell_value cell-number"><?php echo $total; ?></td>
					</tr>
				</tfoot>
			</table>
		</fieldset>

		<fieldset class="section_group">
			<legend><?php echo __('Trained Teachers'); ?></legend>

			<table class="table table-striped table-hover table-bordered page-controls">
				<thead>
					<?php echo $this->Html->tableHeaders(array(__('Education Level'), __('Male'), __('Female'), __('Total'))); ?>
				</thead>

				<?php
				$total = 0;
				?>
				<tbody>
					<?php
					$index = 0;
					$fieldName = 'data[CensusTeacherTraining][%d][%s]';

					foreach ($eduLevelOptions as $eduLevelId => $eduLevelName):
						$subTotal = 0;
						?>
						<tr>
							<td><?php echo $eduLevelName; ?></td>
							<?php
							foreach ($genderOptions AS $genderId => $genderName):
								?>
								<td>
									<div class="input_wrapper">
										<?php
										echo $this->Form->hidden('id', array('name' => sprintf($fieldName, $index, 'id'), 'value' => isset($training[$eduLevelId][$genderId]['censusId']) ? $training[$eduLevelId][$genderId]['censusId'] : 0));
										echo $this->Form->hidden('education_level_id', array('name' => sprintf($fieldName, $index, 'education_level_id'), 'value' => $eduLevelId));
										echo $this->Form->hidden('gender_id', array('name' => sprintf($fieldName, $index, 'gender_id'), 'value' => $genderId));

										$record_tag = '';
										foreach ($source_type as $k => $v):
											if (isset($training[$eduLevelId][$genderId]['source']) && $training[$eduLevelId][$genderId]['source'] == $v) {
												$record_tag = "row_" . $k;
											}
										endforeach;

										if (!empty($training[$eduLevelId][$genderId]['value'])) {
											$value = $training[$eduLevelId][$genderId]['value'];
											$subTotal += $value;
										} else {
											$value = 0;
										}

										echo $this->Form->input('value', array(
											'type' => 'text',
											'class' => $record_tag,
											'name' => sprintf($fieldName, $index, 'value'),
											'computeType' => 'cell_value',
											'value' => $value,
											'maxlength' => 10,
											'onkeypress' => 'return utility.integerCheck(event)',
											'onkeyup' => 'CensusTeachers.computeSubtotal(this)'
										));
										?>
									</div>
								</td>
								<?php
								$index++;
							endforeach;
							?>
							<td class=" cell_number cell_subtotal"><?php echo $subTotal; ?></td>
						</tr>
						<?php
						$total += $subTotal;
					endforeach;
					?>
				</tbody>
				<tfoot>
					<tr>
						<td></td>
						<td></td>
						<td class=" cell_label"><?php echo __('Total'); ?></td>
						<td class=" cell_value cell_number"><?php echo $total; ?></td>
					</tr>
				</tfoot>
			</table>
		</fieldset>

		<fieldset class="section_group">
			<legend><?php echo __('Single Grade Teachers Only'); ?></legend>
			<table class="table table-striped table-hover table-bordered page-controls">
				<thead>
					<?php echo $this->Html->tableHeaders(array(__('Programme'), __('Grade'), __('Male'), __('Female'))); ?>
				</thead>

				<?php if (!empty($singleGradeData)): ?>
					<tbody>
						<?php
						$totalMale = 0;
						$totalFemale = 0;
						$index = 0;
						$fieldName = 'data[CensusTeacher][%d][%s]';

						foreach ($singleGradeData as $programmeName => $programmeData):
							foreach ($programmeData['education_grades'] as $gradeId => $gradeData):
								$gradeName = isset($gradeData['gradeName']) ? $gradeData['gradeName'] : '';
								?>
								<tr>
									<td><?php echo $programmeName; ?></td>
									<td><?php echo $gradeName; ?></td>
									<?php
									foreach ($genderOptions AS $genderId => $genderName):
										?>
										<td>
											<div class="input_wrapper">
												<?php
												$value = 0;
												$record_tag = '';

												if (!empty($gradeData[$genderId]['value'])):
													$value = $gradeData[$genderId]['value'];

													foreach ($source_type as $k => $v):
														if ($gradeData[$genderId]['source'] == $v):
															$record_tag = "row_" . $k;
															break;
														endif;
													endforeach;

													if ($genderName == 'Male'):
														$totalMale += $value;
													else:
														$totalFemale += $value;
													endif;
												endif;

												echo $this->Form->hidden('education_grade_id', array(
													'name' => sprintf('data[CensusTeacher][%d][CensusTeacherGrade][0]', $index),
													'value' => $gradeId
												));
												echo $this->Form->hidden('gender_id', array('name' => sprintf($fieldName, $index, 'gender_id'), 'value' => $genderId));

												echo $this->Form->input('value', array(
													'type' => 'text',
													'class' => $record_tag,
													'name' => sprintf($fieldName, $index, 'value'),
													'computeType' => $genderName == 'Male' ? 'total_male' : 'total_female',
													'value' => $value,
													'maxlength' => 10,
													'onkeypress' => 'return utility.integerCheck(event)',
													'onkeyup' => 'jsTable.computeTotal(this)'
												));
												?>
											</div>
										</td>
										<?php
										$index++;
									endforeach;
									?>
								</tr>
								<?php
							endforeach;
						endforeach;
						?>
					</tbody>
				<?php endif; ?>
				<tfoot>
					<tr>
						<td></td>
						<td class=" cell_label"><?php echo __('Total'); ?></td>
						<td class=" cell_value cell_number total_male"><?php echo $totalMale; ?></td>
						<td class=" cell_value cell_number total_female"><?php echo $totalFemale; ?></td>
					</tr>
				</tfoot>
			</table>
		</fieldset>

		<fieldset class="section_group multi">
			<legend><?php echo __('Multi Grade Teachers'); ?></legend>

			<table class="table table-striped table-hover table-bordered page-controls">
				<thead>
					<?php echo $this->Html->tableHeaders(array(__('Programme'), __('Grade'), __('Male'), __('Female'), '')); ?>
				</thead>

				<?php if (!empty($multiGradeData)): ?>
					<tbody>
						<?php
						$totalMale = 0;
						$totalFemale = 0;
						$index = 0;
						$fieldName = 'data[CensusTeacher][%d][%s]';

						foreach ($multiGradeData as $obj):
							$gradeIndex = 0;
							?>
							<tr>
								<td>
									<?php foreach ($obj['programmes'] as $programmeId => $programmeName) { ?>
										<div class="table_cell_row"><?php echo $programmeName; ?></div>
									<?php } ?>
								</td>
								<td>
									<?php foreach ($obj['grades'] as $gradeId => $gradeName) { ?>
										<div class="table_cell_row">
											<?php
											echo $gradeName;
											echo $this->Form->hidden('education_grade_id', array(
												'name' => sprintf('data[CensusTeacher][%d][CensusTeacherGrade][%d]', $index, $gradeIndex++),
												'value' => $gradeId
											));
											?>
										</div>
									<?php } ?>
								</td>
								<?php
								foreach ($genderOptions AS $genderId => $genderName):
									$value = 0;
									$record_tag = '';
									?>
									<td>
										<div class="input_wrapper">
											<?php
											if (isset($obj['genders'][$genderId])):
												$value = $obj['genders'][$genderId]['value'];

												foreach ($source_type as $k => $v):
													if ($obj['genders'][$genderId]['source'] == $v):
														$record_tag = "row_" . $k;
														break;
													endif;
												endforeach;

												if ($genderName == 'Male') {
													$totalMale += $value;
												} else {
													$totalFemale += $value;
												}
											endif;

											echo $this->Form->hidden('gender_id', array('name' => sprintf($fieldName, $index, 'gender_id'), 'value' => $genderId));

											echo $this->Form->input('value', array(
												'type' => 'text',
												'class' => $record_tag,
												'name' => sprintf($fieldName, $index, 'value'),
												'computeType' => $genderName == 'Male' ? 'total_male' : 'total_female',
												'value' => $value,
												'maxlength' => 10,
												'onkeypress' => 'return utility.integerCheck(event)',
												'onkeyup' => 'jsTable.computeTotal(this)'
											));
											?>
										</div>
									</td>
									<?php
									$index++;
								endforeach;
								?>
								<td>
									<?php echo $this->Utility->getDeleteControl(array('onclick' => "jsTable.computeAllTotal('.multi');")); ?>
								</td>
							</tr>
						<?php endforeach; // end for (multigrade)    ?>
					</tbody>
				<?php endif; // end if empty(multigrade)     ?>

				<tfoot>
					<tr>
						<td></td>
						<td class=" cell_label"><?php echo __('Total'); ?></td>
						<td class=" cell_value cell_number total_male"><?php echo $totalMale; ?></td>
						<td class=" cell_value cell_number total_female"><?php echo $totalFemale; ?></td>
					</tr>
				</tfoot>
			</table>

			<?php if ($_add) { ?>
				<div class="row">
					<a class="void icon_plus" id="add_multi_teacher" url="Census/teachersAddMultiTeacher/<?php echo $selectedYear; ?>"><?php echo __('Add') . ' ' . __('Multi Grade Teacher'); ?></a>
				</div>
			<?php } ?>
		</fieldset>

		<div class="controls">
			<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
			<?php echo $this->Html->link(__('Cancel'), array('action' => 'teachers', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
		</div>

	<?php } // end display content    ?>
	<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>