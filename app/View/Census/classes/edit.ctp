<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('census_classes', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Classes'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'classes', $selectedAcademicPeriod), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

echo $this->Form->create('CensusClass', array(
    'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
    'url' => array('controller' => 'Census', 'action' => 'classesEdit')
));
echo $this->element('census/academic_period_options');
?>

<?php if ($displayContent) { ?>
	<fieldset class="section_group">
		<legend><?php echo __('Single Grade Classes Only'); ?></legend>
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo __('Programme'); ?></th>
					<th class="cell_grade"><?php echo __('Grade'); ?></th>
					<th class="cell_classes"><?php echo __('Classes'); ?></th>
					<th class="cell_classes"><?php echo __('Seats'); ?></th>
				</tr>
			</thead>

			<tbody>
				<?php
				$totalClasses = 0;
				$totalSeats = 0;
				$i = 0;

				foreach ($singleGradeData as $name => $programme) {
					foreach ($programme['education_grades'] as $gradeId => $grade) {
						$totalClasses += $grade['classes'];
						$totalSeats += $grade['seats'];
						$record_tag = "";
						foreach ($source_type as $k => $v) {
							if ($grade['source'] == $v) {
								$record_tag = "row_" . $k;
							}
						}
						?>

						<tr>
							<?php
							echo $this->Form->hidden('education_grade_id', array(
								'name' => sprintf('data[CensusClass][%d][CensusClassGrade][0]', $i),
								'value' => $gradeId
							));
							?>
							<td class="<?php echo $record_tag; ?>"><?php echo $name; ?></td>
							<td class="<?php echo $record_tag; ?>"><?php echo $grade['name']['gradeName']; ?></td>
							<td>
								<div class="input_wrapper">
									<?php
									echo $this->Form->input($i . '.classes', array(
										'type' => 'text',
										'class' => $record_tag,
										'computeType' => 'total_classes',
										'value' => $grade['classes'],
										'maxlength' => 5,
										'onkeypress' => 'return utility.integerCheck(event)',
										'onkeyup' => 'jsTable.computeTotal(this)'
									));
									?>
								</div>
							</td>
							<td class="table_cell">
								<div class="input_wrapper">
									<?php
									echo $this->Form->input($i++ . '.seats', array(
										'type' => 'text',
										'class' => $record_tag,
										'computeType' => 'total_seats',
										'allowNull' => true,
										'value' => $grade['seats'],
										'maxlength' => 10,
										'onkeypress' => 'return utility.integerCheck(event)',
										'onkeyup' => 'jsTable.computeTotal(this)'
									));
									?>
								</div>
							</td>
						</tr>

						<?php
					}
				}
				?>
			</tbody>

			<tfoot>
				<tr>
					<td class="cell_label cell-number" colspan="2"><?php echo __('Total'); ?></td>
					<td class="cell_value cell-number total_classes"><?php echo $totalClasses; ?></td>
					<td class="cell_value cell-number total_seats"><?php echo $totalSeats; ?></td>
				</tr>
			</tfoot>
		</table>
	</fieldset>

	<?php
	$totalClasses = 0;
	$totalSeats = 0;
	?>

	<fieldset class="section_group multi">
		<legend><?php echo __('Multi Grade Classes'); ?></legend>

		<table class="table table-striped table-hover table-bordered page-controls">
			<thead>
				<tr>
					<td><?php echo __('Programme'); ?></td>
					<td class="cell_grade"><?php echo __('Grade'); ?></td>
					<td class="cell_classes"><?php echo __('Classes'); ?></td>
					<td class="cell_classes"><?php echo __('Seats'); ?></td>
					<td class="cell_delete"></td>
				</tr>
			</thead>

			<?php if (!empty($multiGradeData)) { ?>
				<tbody>
					<?php foreach ($multiGradeData as $obj) { ?>
						<tr>
							<?php
							$totalClasses += $obj['classes'];
							$totalSeats += $obj['seats'];
							$gradeIndex = 0;
							$record_tag = "";
							foreach ($source_type as $k => $v) {
								if ($obj['source'] == $v) {
									$record_tag = "row_" . $k;
								}
							}
							?>
							<td class="<?php echo $record_tag; ?>">
								<?php foreach ($obj['programmes'] as $programmeId => $programmeName) { ?>
									<div class="table_cell_row"><?php echo $programmeName; ?></div>
								<?php } ?>
							</td>

							<td class="<?php echo $record_tag; ?>">
								<?php foreach ($obj['grades'] as $gradeId => $gradeName) { ?>
									<div class="table_cell_row">
										<?php
										echo $gradeName;
										echo $this->Form->hidden('education_grade_id', array(
											'name' => sprintf('data[CensusClass][%d][CensusClassGrade][%d]', $i, $gradeIndex++),
											'value' => $gradeId
										));
										?>
									</div>
								<?php } ?>
							</td>

							<td>
								<div class="input_wrapper">
									<?php
									echo $this->Form->input($i . '.classes', array(
										'type' => 'text',
										'class' => $record_tag,
										'computeType' => 'total_classes',
										'value' => $obj['classes'],
										'maxlength' => 5,
										'onkeypress' => 'return utility.integerCheck(event)',
										'onkeyup' => 'jsTable.computeTotal(this)'
									));
									?>
								</div>
							</td>
							<td>
								<div class="input_wrapper">
									<?php
									echo $this->Form->input($i++ . '.seats', array(
										'type' => 'text',
										'class' => $record_tag,
										'computeType' => 'total_seats',
										'allowNull' => true,
										'value' => $obj['seats'],
										'maxlength' => 10,
										'onkeypress' => 'return utility.integerCheck(event)',
										'onkeyup' => 'jsTable.computeTotal(this)'
									));
									?>
								</div>
							</td>
							<td>
								<?php echo $this->Utility->getDeleteControl(array('onclick' => "jsTable.computeAllTotal('.multi');")); ?>
							</td>
						</tr>
					<?php } // end for (multigrade)  ?>
				</tbody>
			<?php } // end if empty(multigrade)  ?>

			<tfoot>
				<tr>
					<td class="cell_label cell-number" colspan="2"><?php echo __('Total'); ?></td>
					<td class="cell_value cell-number total_classes"><?php echo $totalClasses; ?></td>
					<td class="cell_value cell-number total_seats"><?php echo $totalSeats; ?></td>
				</tr>
			</tfoot>
		</table>

		<?php if ($_add) { ?>
			<div class="row">
				<a class="void icon_plus" id="add_multi_class" url="Census/classesAddMultiClass/<?php echo $selectedAcademicPeriod; ?>">
					<?php echo __('Add') . ' ' . __('Multi Grade Class'); ?>
				</a>
			</div>
		<?php } ?>
	</fieldset>

	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'classes', $selectedAcademicPeriod), array('class' => 'btn_cancel btn_left')); ?>
	</div>
<?php } // end display content  ?>
<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>
