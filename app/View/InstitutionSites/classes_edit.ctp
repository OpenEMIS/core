<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

echo $this->Html->script('institution_site_classes', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="classes" class="content_wrapper edit">
    <h1>
        <span><?php echo __('Edit') . ' ' . $className; ?></span>
		<?php
		echo $this->Html->link(__('View'), array('action' => 'classesView', $classId), array('class' => 'divider'));
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
	
	<?php 
	echo $this->Form->create('InstitutionSiteClass', array(
		'url' => array('controller' => 'InstitutionSite', 'action' => 'classesEdit'),
		'inputDefaults' => array('label' => false, 'div' => false)
	));
	$i = 0;
	?>
	
	<div class="table full_width" style="margin-bottom: 20px;">
		<div class="table_head">
			<div class="table_cell cell_year"><?php echo __('Year'); ?></div>
			<div class="table_cell"><?php echo __('Grade'); ?></div>
                        <div class="table_cell"><?php echo __('Number of Seats'); ?></div>
                        <div class="table_cell"><?php echo __('Number of Shifts'); ?></div>
		</div>
		<div class="table_body">
			<div class="table_row">
				<div class="table_cell cell_year"><?php echo $year; ?></div>
				<div class="table_cell">
				<?php foreach($grades as $id => $name) { $i++; ?>
					<div class="table_cell_row <?php echo $i==sizeof($grades) ? 'last' : ''; ?>"><?php echo $name; ?></div>
				<?php } ?>
				</div>
                                <div class="table_cell"><?php echo $no_of_seats; ?></div>
                                <div class="table_cell"><?php echo $no_of_shifts; ?></div>
			</div>
		</div>
	</div>

	<fieldset class="section_group">
        <legend><?php echo __('Subjects'); ?></legend>
        <div class="table">
            <div class="table_head">
                <div class="table_cell cell_year"><?php echo __('Code'); ?></div>
                <div class="table_cell"><?php echo __('Name'); ?></div>
                <div class="table_cell cell_category"><?php echo __('Grade'); ?></div>
                <div class="table_cell cell_delete"></div>
            </div>
            <div class="table_body" url="InstitutionSites/classesSubjectAjax/<?php echo $classId; ?>">
                <?php foreach($subjects as $obj) { ?>
                <div class="table_row" subject-id="<?php echo $obj['InstitutionSiteClassSubject']['education_grade_subject_id']; ?>">
                    <div class="table_cell"><?php echo $obj['EducationSubject']['code']; ?></div>
                    <div class="table_cell"><?php echo $obj['EducationSubject']['name']; ?></div>
                    <div class="table_cell"><?php echo $obj['EducationGrade']['name']; ?></div>
                    <div class="table_cell">
                        <?php echo $this->Utility->getDeleteControl(array('onclick' => 'InstitutionSiteClasses.deleteSubject(this)', 'onDelete' => false)); ?>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
        <div class="row">
            <?php $url = 'InstitutionSites/classesAddSubjectRow/'.$year.'/'.$classId.'/'; ?>
            <a class="void icon_plus subjects" url="<?php echo $url; ?>"><?php echo __('Add').' '.__('Subject'); ?></a>
        </div>
    </fieldset>
	
	<fieldset class="section_group">
		<legend><?php echo __('Teachers'); ?></legend>
		<div class="table">
			<div class="table_head">
				<div class="table_cell cell_id_no"><?php echo __('Identification No'); ?></div>
				<div class="table_cell"><?php echo __('Name'); ?></div>
				<div class="table_cell cell_delete"></div>
			</div>
			<div class="table_body" url="InstitutionSites/classesTeacherAjax/<?php echo $classId; ?>">
				<?php foreach($teachers as $obj) { ?>
				<div class="table_row" teacher-id="<?php echo $obj['Teacher']['id']; ?>" subject-id="<?php echo $obj['InstitutionSiteClassTeacher']['education_subject_id']; ?>">
					<div class="table_cell"><?php echo $obj['Teacher']['identification_no']; ?></div>
					<div class="table_cell"><?php echo $obj['Teacher']['first_name'] . ' ' . $obj['Teacher']['last_name']; ?></div>
					<div class="table_cell">
						<?php echo $this->Utility->getDeleteControl(array('onclick' => 'InstitutionSiteClasses.deleteTeacher(this)', 'onDelete' => false)); ?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="row">
			<?php $url = 'InstitutionSites/classesAddTeacherRow/'.$year.'/'.$classId.'/'; ?>
			<a class="void icon_plus teachers" url="<?php echo $url; ?>"><?php echo __('Add').' '.__('Teacher'); ?></a>
		</div>
	</fieldset>
	
	<fieldset class="section_group">
		<legend><?php echo __('Students'); ?></legend>
		<?php foreach($grades as $id => $name) { ?>
	
		<fieldset class="section_break">
			<legend>
				<span><?php echo $name ?></span>
			</legend>
			
			<div class="table">
				<div class="table_head">
					<div class="table_cell cell_id_no"><?php echo __('Identification No'); ?></div>
					<div class="table_cell"><?php echo __('Name'); ?></div>
					<div class="table_cell cell_category"><?php echo __('Category'); ?></div>
					<div class="table_cell cell_delete"></div>
				</div>
				
				<div class="table_body" url="InstitutionSites/classesStudentAjax/<?php echo $id; ?>">
					<?php if(isset($students[$id])) { ?>
					<?php foreach($students[$id] as $obj) { ?>
					<div class="table_row" student-id="<?php echo $obj['id']; ?>">
						<div class="table_cell"><?php echo $obj['identification_no']; ?></div>
						<div class="table_cell"><?php echo $obj['first_name'] . ' ' . $obj['last_name']; ?></div>
						<div class="table_cell"><?php echo $obj['category']; ?></div>
						<div class="table_cell">
							<?php echo $this->Utility->getDeleteControl(array(
								'onclick' => 'InstitutionSiteClasses.deleteStudent(this)',
								'onDelete' => false
							));
							?>
						</div>
					</div>
					<?php } // end for ?>
					<?php } // end if ?>
				</div>
			</div>
			
			<div class="row">
				<?php $url = 'InstitutionSites/classesAddStudentRow/'.$year.'/'.$id; ?>
				<a class="void icon_plus students" url="<?php echo $url; ?>"><?php echo __('Add').' '.__('Student'); ?></a>
			</div>
		</fieldset>
		<?php } ?>
	</fieldset>
</div>
