<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('assessment', 'stylesheet', array('inline' => false));

echo $this->Html->script('institution_site_assessments', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="assessment" class="content_wrapper">
	<h1>
		<span><?php echo __('Assessments'); ?></span>
		<?php
		if($_edit && !empty($data)) {
			echo $this->Html->link(__('Edit'), array('action' => 'assessmentsListEdit', $selectedYear, $selectedProgramme), array('class' => 'divider'));
		}
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'assessmentsAdd', $selectedYear), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="filter_wrapper">
		<div class="row edit">
			<div class="label"><?php echo __('Year'); ?></div>
			<div class="value">
				<?php
				echo $this->Form->input('school_year_id', array(
					'id' => 'SchoolYearId',
					'label' => false,
					'div' => false,
					'options' => $yearOptions,
					'default' => $selectedYear,
					'onchange' => 'InstitutionSiteAssessments.navigateProgramme(this, false)',
					'url' => 'InstitutionSites/assessmentsList'
				));
				?>
			</div>
		</div>
		
		<div class="row edit">
			<div class="label"><?php echo __('Education Programme'); ?></div>
			<div class="value">
				<?php
				echo $this->Form->input('education_programme_id', array(
					'id' => 'EducationProgrammeId',
					'label' => false,
					'div' => false,
					'class' => 'default',
					'options' => $programmeOptions,
					'default' => $selectedProgramme,
					'onchange' => 'InstitutionSiteAssessments.navigateProgramme(this, true)',
					'url' => 'InstitutionSites/assessmentsList/'
				));
				?>
			</div>
		</div>
	</div>
	
	<?php foreach($data as $key => $obj) { ?>
	<fieldset class="section_group">
		<legend><?php echo $obj['name']; ?></legend>
		<div class="table allow_hover" action="InstitutionSites/assessmentsView/">
			<div class="table_head">
				<div class="table_cell cell_code"><?php echo __('Code'); ?></div>
				<div class="table_cell cell_name"><?php echo __('Name'); ?></div>
				<div class="table_cell"><?php echo __('Description'); ?></div>
			</div>
			<div class="table_body">
				<?php foreach($obj['assessment'][$type] as $item) { ?>
				<div class="table_row <?php echo $item['visible'] == 0 ? 'inactive' : ''; ?>" row-id="<?php echo $item['id']; ?>">
					<div class="table_cell"><?php echo $item['code']; ?></div>
					<div class="table_cell"><?php echo $item['name']; ?></div>
					<div class="table_cell"><?php echo $item['description']; ?></div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	<?php } ?>
</div>
