<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

echo $this->Html->script('institution_site_programmes', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="programmes" class="content_wrapper">
	<?php
	echo $this->Form->create('InstitutionSiteStudent', array(
		'id' => 'submitForm',
		'onsubmit' => 'return false',
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => false),
		'url' => array('controller' => 'InstitutionSites', 'action' => 'programmesView')
	));
	?>
	<h1>
		<span><?php echo __('Programme Details'); ?></span>
		<?php
		echo $this->Html->link(__('List'), array('action' => 'programmes'), array('class' => 'divider'));
		if($_edit && !empty($programmeOptions)) {
			echo $this->Html->link(__('Edit'), array('action' => 'programmesEdit', $selectedYear, $selectedProgramme), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<fieldset class="section_group">
		<legend><?php echo __('Details'); ?></legend>
		<div class="row edit">
			<div class="label"><?php echo __('Year'); ?></div>
			<div class="value">
				<?php
				echo $this->Form->input('school_year_id', array(
					'id' => 'SchoolYearId',
					'options' => $yearOptions,
					'default' => $selectedYear,
					'onchange' => 'InstitutionSiteProgrammes.navigate()'
				));
				?>
			</div>
		</div>
		
		<div class="row edit">
			<div class="label"><?php echo __('Programme'); ?></div>
			<div class="value">
				<?php
				echo $this->Form->input('education_programme_id', array(
					'id' => 'EducationProgrammeId',
					'class' => 'select',
					'options' => $programmeOptions,
					'default' => $selectedProgramme,
					'onchange' => 'InstitutionSiteProgrammes.navigate()'
				));
				?>
			</div>
		</div>
	</fieldset>
	
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