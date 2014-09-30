<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Teachers'));

$this->start('contentActions');
if ($_edit && $isEditable) {
	echo $this->Html->link(__('Edit'), array('action' => 'teachersEdit', $selectedYear), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('census/year_options');
?>

<div class="table-responsive">

	<?php if ($displayContent) { ?>
		<fieldset class="section_group">
			<legend><?php echo __('Full Time Equivalent Teachers'); ?></legend>

			<table class="table table-striped table-hover table-bordered">
				<thead>
					<?php echo $this->Html->tableHeaders(array(__('Education Level'), __('Male'), __('Female'), __('Total'))); ?>
				</thead>
				<?php
				$totalFte = 0;
				?>
				<tbody>
					<?php
					$tableDataFte = array();
					foreach ($eduLevelOptions as $eduLevelId => $eduLevelName) {
						$rowTotal = 0;

						$maleValue = 0;
						$femaleValue = 0;

						$recordTagMale = "";
						$recordTagFemale = "";

						foreach ($genderOptions AS $genderId => $genderName) {
							if (!empty($fte[$eduLevelId][$genderId])) {
								foreach ($source_type as $k => $v) {
									if ($fte[$eduLevelId][$genderId]['source'] == $v) {
										if ($genderOptions[$genderId] == 'Male') {
											$recordTagMale = "row_" . $k;
										} else {
											$recordTagFemale = "row_" . $k;
										}
									}
								}

								if ($genderName == 'Male') {
									$maleValue = $fte[$eduLevelId][$genderId]['value'];
								} else {
									$femaleValue = $fte[$eduLevelId][$genderId]['value'];
								}
							}
						}

						$rowTotal = $maleValue + $femaleValue;
						$totalFte += $rowTotal;

						$tableDataFte[] = array(
							$eduLevelName,
							array($maleValue, array('class' => 'cell-number')),
							array($femaleValue, array('class' => 'cell-number')),
							array($rowTotal, array('class' => 'cell-number'))
						);
					}
					echo $this->Html->tableCells($tableDataFte, array('class' => ''));
					?>

				</tbody>
				<tfoot>
					<tr>
						<td class="cell-number" colspan="3"><?php echo __('Total'); ?></td>
						<td class="cell-number"><?php echo $totalFte; ?></td>
					</tr>
				</tfoot>
			</table>
		</fieldset>

		<fieldset class="section_group">
			<legend><?php echo __('Trained Teachers'); ?></legend>

			<table class="table table-striped table-hover table-bordered">
				<thead>
					<?php echo $this->Html->tableHeaders(array(__('Education Level'), __('Male'), __('Female'), __('Total'))); ?>
				</thead>

				<?php
				$totalTraining = 0;
				?>
				<tbody>
					<?php
					$tableDataTraining = array();
					foreach ($eduLevelOptions as $eduLevelId => $eduLevelName) {
						$rowTotal = 0;

						$maleValue = 0;
						$femaleValue = 0;

						$recordTagMale = "";
						$recordTagFemale = "";

						foreach ($genderOptions AS $genderId => $genderName) {
							if (!empty($training[$eduLevelId][$genderId])) {
								foreach ($source_type as $k => $v) {
									if ($training[$eduLevelId][$genderId]['source'] == $v) {
										if ($genderOptions[$genderId] == 'Male') {
											$recordTagMale = "row_" . $k;
										} else {
											$recordTagFemale = "row_" . $k;
										}
									}
								}

								if ($genderName == 'Male') {
									$maleValue = $training[$eduLevelId][$genderId]['value'];
								} else {
									$femaleValue = $training[$eduLevelId][$genderId]['value'];
								}
							}
						}

						$rowTotal = $maleValue + $femaleValue;
						$totalTraining += $rowTotal;

						$tableDataTraining[] = array(
							$eduLevelName,
							array($maleValue, array('class' => 'cell-number')),
							array($femaleValue, array('class' => 'cell-number')),
							array($rowTotal, array('class' => 'cell-number'))
						);
					}
					echo $this->Html->tableCells($tableDataTraining, array('class' => ''));
					?>
				</tbody>
				<tfoot>
					<tr>
						<td class="cell-number" colspan="3"><?php echo __('Total'); ?></td>
						<td class="cell-number"><?php echo $totalTraining; ?></td>
					</tr>
				</tfoot>
			</table>
		</fieldset>

		<fieldset class="section_group">
			<legend><?php echo __('Single Grade Teachers Only'); ?></legend>

			<table class="table table-striped table-hover table-bordered">
				<thead>
					<?php echo $this->Html->tableHeaders(array(__('Programme'), __('Grade'), __('Male'), __('Female'))); ?>
				</thead>

				<?php if (!empty($singleGradeData)): ?>
					<tbody>
						<?php
						$totalMale = 0;
						$totalFemale = 0;
						$tableData = array();

						foreach ($singleGradeData as $programmeName => $programmeData):
							foreach ($programmeData['education_grades'] as $gradeId => $gradeData):
								$maleValue = 0;
								$femaleValue = 0;

								$recordTagMale = "";
								$recordTagFemale = "";
								$gradeName = isset($gradeData['gradeName']) ? $gradeData['gradeName'] : '';

								foreach ($genderOptions AS $genderId => $genderName):
									if (!empty($gradeData[$genderId]['value'])) {
										foreach ($source_type as $k => $v):
											if ($gradeData[$genderId]['source'] == $v) {
												if ($genderName == 'Male') {
													$recordTagMale = "row_" . $k;
												} else {
													$recordTagFemale = "row_" . $k;
												}
											}
										endforeach;

										if ($genderName == 'Male') {
											$maleValue = $gradeData[$genderId]['value'];
										} else {
											$femaleValue = $gradeData[$genderId]['value'];
										}
									}
								endforeach;

								$totalMale += $maleValue;
								$totalFemale += $femaleValue;

								$tableData[] = array(
									$programmeName,
									array($gradeName, array('class' => '')),
									array($maleValue, array('class' => 'cell-number ' . $recordTagMale)),
									array($femaleValue, array('class' => 'cell-number ' . $recordTagFemale))
								);
							endforeach;
						endforeach;

						echo $this->Html->tableCells($tableData, array('class' => ''));
						?>
					</tbody>
				<?php endif; ?>
				<tfoot>
					<tr>
						<td class="cell-number" colspan="2"><?php echo __('Total'); ?></td>
						<td class="cell-number"><?php echo $totalMale; ?></td>
						<td class="cell-number"><?php echo $totalFemale; ?></td>
					</tr>
				</tfoot>
			</table>
		</fieldset>

		<fieldset class="section_group multi">
			<legend><?php echo __('Multi Grade Teachers'); ?></legend>

			<table class="table table-striped table-hover table-bordered">
				<thead>
					<?php echo $this->Html->tableHeaders(array(__('Programme'), __('Grade'), __('Male'), __('Female'))); ?>
				</thead>

				<?php
				if (!empty($multiGradeData)):
					?>
					<tbody>
						<?php
						$totalMale = 0;
						$totalFemale = 0;
						$tableData = array();
						foreach ($multiGradeData as $obj):
							?>
							<tr>
								<td>
									<?php foreach ($obj['programmes'] as $programmeId => $programmeName) { ?>
										<div class="table_cell_row"><?php echo $programmeName; ?></div>
									<?php } ?>
								</td>
								<td>
									<?php foreach ($obj['grades'] as $gradeId => $gradeName) { ?>
										<div class="table_cell_row"><?php echo $gradeName; ?></div>
									<?php } ?>
								</td>
								<?php
								$maleValue = 0;
								$femaleValue = 0;
								$recordTagMale = "";
								$recordTagFemale = "";

								foreach ($genderOptions AS $genderId => $genderName) {
									if (isset($obj['genders'][$genderId])) {
										foreach ($source_type as $k => $v) {
											if ($obj['genders'][$genderId]['source'] == $v) {
												if ($genderName == 'Male') {
													$recordTagMale = "row_" . $k;
												} else {
													$recordTagFemale = "row_" . $k;
												}
											}
										}

										if ($genderName == 'Male') {
											$maleValue = $obj['genders'][$genderId]['value'];
										} else {
											$femaleValue = $obj['genders'][$genderId]['value'];
										}
									}
								}

								$totalMale += $maleValue;
								$totalFemale += $femaleValue;
								?>
								<td class="cell-number <?php echo $recordTagMale; ?>"><?php echo $maleValue; ?></td>
								<td class="cell-number <?php echo $recordTagFemale; ?>"><?php echo $femaleValue; ?></td>
							</tr> 
						<?php endforeach; // end for (multigrade)   ?>
					</tbody>
				<?php endif; // end if empty(multigrade)   ?>

				<tfoot>
					<tr>
						<td></td>
						<td><?php echo __('Total'); ?></td>
						<td class="cell-number"><?php echo $totalMale; ?></td>
						<td class="cell-number"><?php echo $totalFemale; ?></td>
					</tr>
				</tfoot>
			</table>
		</fieldset>

	<?php } // end display content   ?>
</div>
<?php $this->end(); ?>
