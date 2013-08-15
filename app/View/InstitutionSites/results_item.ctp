<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('assessment', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

echo $this->Html->script('institution_site_results', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="assessment_item" class="content_wrapper">
	<h1>
		<span><?php echo $data['AssessmentItemType']['name']; ?></span>
		<?php echo $this->Html->link(__('Back'), array('action' => 'resultsDetails', $selectedYear, $data['AssessmentItemType']['id']), array('class' => 'divider')); ?>
		<?php
		if($_edit && $data['AssessmentItemType']['visible']==1) {
			echo $this->Html->link(__('Edit'), array('action' => 'resultsItemEdit', $data['AssessmentItem']['id'], $selectedYear, $selectedClass), array('class' => 'divider'));
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
					'onchange' => 'InstitutionSiteResults.navigateClass(this, true)',
					'url' => 'InstitutionSites/resultsItem/' . $data['AssessmentItem']['id']
				));
				?>
			</div>
		</div>
		
		<div class="row edit">
			<div class="label"><?php echo __('Classes'); ?></div>
			<div class="value">
				<?php
				echo $this->Form->input('institution_site_class_id', array(
					'id' => 'InstitutionSiteClassId',
					'label' => false,
					'div' => false,
					'class' => 'default',
					'options' => $classOptions,
					'default' => $selectedClass,
					'onchange' => 'InstitutionSiteResults.navigateClass(this, true)',
					'url' => 'InstitutionSites/resultsItem/' . $data['AssessmentItem']['id']
				));
				?>
			</div>
		</div>
	</div>
	
	<fieldset class="section_group">
		<legend><?php echo $data['EducationSubject']['name']; ?></legend>
		<div class="table full_width">
			<div class="table_head">
				<div class="table_cell cell_id_no"><?php echo __('Identification No'); ?></div>
				<div class="table_cell"><?php echo __('Student Name'); ?></div>
				<div class="table_cell cell_marks"><?php echo __('Marks'); ?></div>
				<div class="table_cell cell_grading"><?php echo __('Grading'); ?></div>
			</div>
			<div class="table_body">
				<?php foreach($students as $obj) { ?>
				<div class="table_row">
					<div class="table_cell"><?php echo $obj['Student']['identification_no']; ?></div>
					<div class="table_cell"><?php echo sprintf('%s %s', $obj['Student']['first_name'], $obj['Student']['last_name']); ?></div>
					<div class="table_cell center">
					<?php 
					$marks = $obj['AssessmentItemResult']['marks'];
					if(is_null($marks) || strlen(trim($marks))==0) {
						echo __('Not Recorded');
					} else {
						if($marks < $data['AssessmentItem']['min']) {
							echo sprintf('<span class="red">%s</span>', $marks);
						} else {
							echo $marks;
						}
					}
					?>
					</div>
					<div class="table_cell center"><?php echo $obj['AssessmentResultType']['name']; ?></div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
</div>
