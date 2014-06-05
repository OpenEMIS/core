<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $className);

$this->start('contentActions');
echo $this->Html->link(__('List'), array('action' => 'classes', $yearId), array('class' => 'divider'));
if ($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'classesEdit', $classId), array('class' => 'divider'));
}
if ($_delete) {
	echo $this->Html->link(__('Delete'), array('action' => 'classesDelete', $classId), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
if ($_accessControl->check($this->params['controller'], 'classesAttendance')) {
	echo $this->Html->link(__('Attendance'), array('action' => 'classesAttendance'), array('class' => 'divider'));
}
if ($_accessControl->check($this->params['controller'], 'classesAssessments')) {
	echo $this->Html->link(__('Results'), array('action' => 'classesAssessments', $classId), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
?>
<div id="classes" class="content_wrapper">

	<?php
	$i = 0;
	?>

	<table class="table table-striped table-hover table-bordered" style="margin-bottom: 20px;">
		<thead>
			<tr>
				<th class="table_cell cell_year"><?php echo __('Year'); ?></th>
				<th class="table_cell"><?php echo __('Grade'); ?></th>
				<th class="table_cell"><?php echo __('Seats'); ?></th>
				<th class="table_cell"><?php echo __('Shift'); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="table_cell cell_year"><?php echo $year; ?></td>
				<td class="table_cell">
					<?php
					foreach ($grades as $id => $name) {
						$i++;
						?>
						<div class="table_cell_row <?php echo $i == sizeof($grades) ? 'last' : ''; ?>"><?php echo $name; ?></div>
					<?php } ?>
				</td>
				<td class="table_cell"><?php echo $noOfSeats; ?></td>
				<td class="table_cell"><?php echo $noOfShifts; ?></td>
			</tr>
		</tbody>
	</table>

	<fieldset class="section_group">
		<legend><?php echo __('Subjects'); ?></legend>
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th class="table_cell cell_year"><?php echo __('Code'); ?></th>
                    <th class="table_cell"><?php echo __('Name'); ?></th>
                    <th class="table_cell cell_category"><?php echo __('Grade'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($subjects as $obj) { ?>
					<tr>
						<td class="table_cell"><?php echo $obj['EducationSubject']['code']; ?></td>
						<td class="table_cell"><?php echo $obj['EducationSubject']['name']; ?></td>
						<td class="table_cell"><?php echo $obj['EducationGrade']['name']; ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</fieldset>

	<fieldset class="section_group">
		<legend><?php echo __('Staff'); ?></legend>
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th class="table_cell cell_id_no"><?php echo __('OpenEMIS ID'); ?></th>
					<th class="table_cell"><?php echo __('Name'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($staffs as $obj) { ?>
					<tr>
						<td class="table_cell"><?php echo $obj['Staff']['identification_no']; ?></td>
						<td class="table_cell"><?php echo $obj['Staff']['first_name'] . ' ' . $obj['Staff']['last_name']; ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</fieldset>

	<fieldset class="section_group">
		<legend><?php echo __('Students'); ?></legend>
		<?php foreach ($grades as $id => $name) { ?>

			<fieldset class="section_break">
				<legend><?php echo $name ?></legend>

				<table class="table table-striped table-hover table-bordered">
					<thead>
						<tr>
							<th class="table_cell cell_id_no"><?php echo __('OpenEMIS ID'); ?></th>
							<th class="table_cell"><?php echo __('Name'); ?></th>
							<th class="table_cell cell_category"><?php echo __('Category'); ?></th>
						</tr>
					</thead>

					<tbody>
						<?php if (isset($students[$id])) { ?>
							<?php foreach ($students[$id] as $obj) { ?>
								<tr>
									<td class="table_cell"><?php echo $obj['identification_no']; ?></td>
									<td class="table_cell"><?php echo $obj['first_name'] . ' ' . $obj['middle_name'] . ' ' . $obj['last_name']; ?></td>
									<td class="table_cell"><?php echo $obj['category']; ?></td>
								</tr>
							<?php } // end for ?>
						<?php } // end if    ?>
					</tbody>
				</table>
			</fieldset>

		<?php } ?>
	</fieldset>
</div>
<?php $this->end(); ?>