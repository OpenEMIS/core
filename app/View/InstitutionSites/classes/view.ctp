<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $className);

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'classes', $yearId), array('class' => 'divider'));
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
$i = 0;
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th class="cell_year"><?php echo __('Year'); ?></th>
				<th><?php echo __('Grade'); ?></th>
				<th><?php echo __('Seats'); ?></th>
				<th><?php echo __('Shift'); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="cell_year"><?php echo $year; ?></td>
				<td>
					<?php
					foreach ($grades as $id => $name) {
						$i++;
						?>
						<div class="table_cell_row <?php echo $i == sizeof($grades) ? 'last' : ''; ?>"><?php echo $name; ?></div>
					<?php } ?>
				</td>
				<td><?php echo $noOfSeats; ?></td>
				<td><?php echo $noOfShifts; ?></td>
			</tr>
		</tbody>
	</table>
</div>

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
				<th class="cell_id_no"><?php echo __('OpenEMIS ID'); ?></th>
				<th><?php echo __('Name'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($staffs as $obj) { ?>
				<tr>
					<td><?php echo $obj['Staff']['identification_no']; ?></td>
					<td><?php echo $obj['Staff']['first_name'] . ' ' . $obj['Staff']['last_name']; ?></td>
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
						<th class="cell_id_no"><?php echo __('OpenEMIS ID'); ?></th>
						<th><?php echo __('Name'); ?></th>
						<th class="cell_category"><?php echo __('Category'); ?></th>
					</tr>
				</thead>

				<tbody>
					<?php if (isset($students[$id])) { ?>
						<?php foreach ($students[$id] as $obj) { ?>
							<tr>
								<td><?php echo $obj['identification_no']; ?></td>
								<td><?php echo $obj['first_name'] . ' ' . $obj['middle_name'] . ' ' . $obj['last_name']; ?></td>
								<td><?php echo $obj['category']; ?></td>
							</tr>
						<?php } // end for ?>
					<?php } // end if    ?>
				</tbody>
			</table>
		</fieldset>

	<?php } ?>
</fieldset>

<?php $this->end(); ?>
