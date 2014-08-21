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
				<tbody>
					<?php
					$total = 0;
					$tableData = array();
					foreach ($fte as $record) {
						$total += $record['male'] + $record['female'];
						$record_tag = "";
						foreach ($source_type as $k => $v) {
							if ($record['source'] == $v) {
								$record_tag = "row_" . $k;
							}
						}

						$eduLevelName = $record['education_level_name'];
						$male = is_null($record['male']) || (!$record['male'] > 0) ? 0 : str_replace(".0", "", $record['male']);
						$female = is_null($record['female']) || (!$record['female'] > 0) ? 0 : str_replace(".0", "", $record['female']);
						$rowTotal = $record['male'] + $record['female'];

						$tableData[] = array(
							$eduLevelName,
							array($male, array('class' => 'cell-number')),
							array($female, array('class' => 'cell-number')),
							array($rowTotal, array('class' => 'cell-number'))
						);
					}
					echo $this->Html->tableCells($tableData, array('class' => $record_tag));
					?>

				</tbody>
				<tfoot>
					<tr>
						<td class="cell-number" colspan="3"><?php echo __('Total'); ?></td>
						<td class="cell-number"><?php echo $total; ?></td>
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

				<tbody>
					<?php
					$total = 0;
					$tableData = array();
					foreach ($training as $record) {
						$total += $record['male'] + $record['female'];
						$total += $record['male'] + $record['female'];
						$record_tag = "";
						foreach ($source_type as $k => $v) {
							if ($record['source'] == $v) {
								$record_tag = "row_" . $k;
							}
						}

						$eduLevelName = $record['education_level_name'];
						$male = is_null($record['male']) ? 0 : $record['male'];
						$female = is_null($record['female']) ? 0 : $record['female'];
						$rowTotal = $record['male'] + $record['female'];

						$tableData[] = array(
							$eduLevelName,
							array($male, array('class' => 'cell-number')),
							array($female, array('class' => 'cell-number')),
							array($rowTotal, array('class' => 'cell-number'))
						);
					}
					echo $this->Html->tableCells($tableData, array('class' => $record_tag));
					?>
				</tbody>

				<tfoot>
					<tr>
				  		<td class="cell-number" colspan="3"><?php echo __('Total'); ?></td>
						<td class="cell-number"><?php echo $total; ?></td>
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

				<tbody>
					<?php
					$totalMale = 0;
					$totalFemale = 0;
					$tableData = array();

					foreach ($singleGradeData as $name => $programme) {
						foreach ($programme['education_grades'] as $gradeId => $grade) {
							$totalMale += $grade['male'];
							$totalFemale += $grade['female'];
							$record_tag = "";
							foreach ($source_type as $k => $v) {
								if ($grade['source'] == $v) {
									$record_tag = "row_" . $k;
								}
							}

							$tableData[] = array(
								$name,
								array($grade['name'], array('class' => 'cell-number')),
								array($grade['male'], array('class' => 'cell-number')),
								array($grade['female'], array('class' => 'cell-number'))
							);
							?>

							<?php
						}
					}

					echo $this->Html->tableCells($tableData, array('class' => $record_tag));
					?>
				</tbody>
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
				$totalMale = 0;
				$totalFemale = 0;
				$tableData = array();
				if (!empty($multiGradeData)) {
					?>
					<tbody>
						<?php foreach ($multiGradeData as $obj) { ?>
							<tr>
								<?php
								$totalMale += $obj['male'];
								$totalFemale += $obj['female'];
								$record_tag = "";
								foreach ($source_type as $k => $v) {
									if ($obj['source'] == $v) {
										$record_tag = "row_" . $k;
									}
								}
								?>
								<td class=" <?php echo $record_tag; ?>">
									<?php foreach ($obj['programmes'] as $programmeId => $programmeName) { ?>
										<div class="table_cell_row"><?php echo $programmeName; ?></div>
									<?php } ?>
								</td>

								<td class=" <?php echo $record_tag; ?>">
									<?php foreach ($obj['grades'] as $gradeId => $gradeName) { ?>
										<div class="table_cell_row"><?php echo $gradeName; ?></div>
									<?php } ?>
								</td>

								<td class="cell-number <?php echo $record_tag; ?>"><?php echo $obj['male']; ?></td>
								<td class="cell-number <?php echo $record_tag; ?>"><?php echo $obj['female']; ?></td>
							</tr> 
						<?php } // end for (multigrade)   ?>
					</tbody>
				<?php } // end if empty(multigrade)  ?>

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
