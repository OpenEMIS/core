<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

echo $this->Html->script('institution_site_students', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="students" class="content_wrapper">
	<?php
	echo $this->Form->create('InstitutionSiteProgrammeStudent', array(
		'id' => 'submitForm',
		'onsubmit' => 'return false',
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'InstitutionSites', 'action' => 'studentsView')
	));
	?>
	<h1>
		<span><?php echo __('Programmes'); ?></span>
		<?php
		echo $this->Html->link(__('Search'), array('action' => 'studentsList'), array('class' => 'divider'));
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'studentsEdit', $selectedYear, $selectedProgramme), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="row edit">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'id' => 'SchoolYearId',
				'options' => $yearOptions,
				'default' => $selectedYear,
				'onchange' => 'InstitutionSiteStudents.navigate()'
			));
			?>
		</div>
	</div>
	
	<div class="row edit">
		<div class="label"><?php echo __('Programme'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('institution_site_programme_id', array(
				'id' => 'InstitutionSiteProgrammeId',
				'class' => 'select',
				'options' => $programmeOptions,
				'default' => $selectedProgramme,
				'onchange' => 'InstitutionSiteStudents.navigate()'
			));
			?>
		</div>
	</div>
	
	<fieldset class="section_group" id="student_group_view">
		<legend><?php echo __('Students'); ?></legend>
		
		<div class="table_scrollable <?php echo sizeof($data) > 20 ? 'scroll_active' : ''; ?>">
			<div class="table table_header">
				<div class="table_head">
					<div class="table_cell cell_id_no"><?php echo __('Identification No'); ?></div>
					<div class="table_cell"><?php echo __('Name'); ?></div>
					<div class="table_cell cell_date"><?php echo __('Start Date'); ?></div>
					<div class="table_cell cell_date"><?php echo __('Completion Date'); ?></div>
				</div>
			</div>
				
			<div class="list_wrapper">
				<div class="table">
					<div class="table_body">
						<?php foreach($data as $obj) { ?>
						<div class="table_row">
							<div class="table_cell cell_id_no"><?php echo $obj['identification_no']; ?></div>
							<div class="table_cell "><?php echo $obj['first_name'] . ' ' . $obj['last_name']; ?></div>
							<div class="table_cell cell_date center"><?php echo $obj['start_date']; ?></div>
							<div class="table_cell cell_date center"><?php echo $obj['end_date']; ?></div>
						</div>
						<?php } ?>
					</div>
				</div>
			</div> <!-- end table -->
		</div> <!-- end table scrollable -->
	</fieldset>
	<?php echo $this->Form->end(); ?>
</div>