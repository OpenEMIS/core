<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

echo $this->Html->script('institution_site_classes', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Edit') . ' ' . $className);

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'classesView', $classId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>

<div id="classes" class="content_wrapper edit">

	<?php
	echo $this->Form->create('InstitutionSiteClass', array(
		'url' => array('controller' => 'InstitutionSites', 'action' => 'classesEdit', $classId),
		'inputDefaults' => array('label' => false, 'div' => false)
	));
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
				<td class="table_cell"><?php echo $this->Form->input('no_of_seats', array('id' => 'NoOfSeats', 'value' => $noOfSeats, 'class' => 'default inlineShortField form-control')); ?></td>
				<td class="table_cell"><?php echo $this->Form->input('no_of_shifts', array('id' => 'NoOfShifts', 'options' => $shiftOptions, 'value' => $noOfShifts, 'class' => 'default inlineShortField form-control')); ?></td>
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
					<th class="table_cell cell_delete"></th>
				</tr>
            </thead>
            <tbody url="InstitutionSites/classesSubjectAjax/<?php echo $classId; ?>">
				<?php foreach ($subjects as $obj) { ?>
					<tr subject-id="<?php echo $obj['InstitutionSiteClassSubject']['education_grade_subject_id']; ?>">
						<td class="table_cell"><?php echo $obj['EducationSubject']['code']; ?></td>
						<td class="table_cell"><?php echo $obj['EducationSubject']['name']; ?></td>
						<td class="table_cell"><?php echo $obj['EducationGrade']['name']; ?></td>
						<td class="table_cell">
							<?php echo $this->Utility->getDeleteControl(array('onclick' => 'InstitutionSiteClasses.deleteSubject(this)', 'onDelete' => false)); ?>
						</td>
					</tr>
				<?php } ?>
            </tbody>
        </table>
        <div class="row">
			<?php $url = 'InstitutionSites/classesAddSubjectRow/' . $year . '/' . $classId . '/'; ?>
            <a class="void icon_plus subjects" url="<?php echo $url; ?>"><?php echo __('Add') . ' ' . __('Subject'); ?></a>
        </div>
    </fieldset>

	<fieldset class="section_group">
		<legend><?php echo __('Teachers'); ?></legend>
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th class="table_cell cell_id_no"><?php echo __('OpenEMIS ID'); ?></th>
					<th class="table_cell"><?php echo __('Name'); ?></th>
					<th class="table_cell cell_delete"></th>
				</tr>
			</thead>
			<tbody url="InstitutionSites/classesTeacherAjax/<?php echo $classId; ?>">
				<?php foreach ($teachers as $obj) { ?>
					<tr teacher-id="<?php echo $obj['Teacher']['id']; ?>" subject-id="<?php echo $obj['InstitutionSiteClassTeacher']['education_subject_id']; ?>">
						<td class="table_cell"><?php echo $obj['Teacher']['identification_no']; ?></td>
						<td class="table_cell"><?php echo $obj['Teacher']['first_name'] . ' ' . $obj['Teacher']['last_name']; ?></td>
						<td class="table_cell">
							<?php echo $this->Utility->getDeleteControl(array('onclick' => 'InstitutionSiteClasses.deleteTeacher(this)', 'onDelete' => false)); ?>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		<div class="row">
			<?php $url = 'InstitutionSites/classesAddTeacherRow/' . $year . '/' . $classId . '/'; ?>
			<a class="void icon_plus teachers" url="<?php echo $url; ?>"><?php echo __('Add') . ' ' . __('Teacher'); ?></a>
		</div>
	</fieldset>

	<fieldset class="section_group students">
		<legend><?php echo __('Students'); ?></legend>
		<?php foreach ($grades as $id => $name) { ?>

			<fieldset class="section_break">
				<legend>
					<span><?php echo $name ?></span>
				</legend>

				<table class="table table-striped table-hover table-bordered">
					<thead>
						<tr>
							<th class="table_cell cell_id_no"><?php echo __('OpenEMIS ID'); ?></th>
							<th class="table_cell"><?php echo __('Name'); ?></th>
							<th class="table_cell cell_category"><?php echo __('Category'); ?></th>
							<th class="table_cell cell_delete"></th>
						</tr>
					</thead>

					<tbody url="InstitutionSites/classesStudentAjax/<?php echo $id; ?>">
						<?php if (isset($students[$id])) { ?>
							<?php foreach ($students[$id] as $obj) { ?>
								<tr student-id="<?php echo $obj['id']; ?>">
									<td class="table_cell"><?php echo $obj['identification_no']; ?></td>
									<td class="table_cell"><?php echo $obj['first_name'] . ' ' . $obj['middle_name'] . ' ' . $obj['last_name']; ?></td>
									<td class="table_cell" attr="category">
										<?php
										echo $this->Form->input('student_category_id', array(
											'label' => false,
											'div' => false,
											'class' => 'full_width form-control',
											'options' => $studentCategoryOptions,
											'onchange' => 'InstitutionSiteClasses.changeStudentCategory(this)',
											'default' => $obj['category_id']
										));
										?>
									</td>
									<td class="table_cell">
										<?php
										echo $this->Utility->getDeleteControl(array(
											'onclick' => 'InstitutionSiteClasses.deleteStudent(this)',
											'onDelete' => false
										));
										?>
									</td>
								</tr>
							<?php } // end for ?>
						<?php } // end if   ?>
					</tbody>
				</table>

				<div class="row">
					<?php $url = 'InstitutionSites/classesAddStudentRow/' . $year . '/' . $id; ?>
					<a class="void icon_plus students" url="<?php echo $url; ?>"><?php echo __('Add') . ' ' . __('Student'); ?></a>
				</div>
			</fieldset>
		<?php } ?>
	</fieldset>
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'classesView', $classId), array('class' => 'btn_cancel btn_left')); ?>
	</div>

	<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>
