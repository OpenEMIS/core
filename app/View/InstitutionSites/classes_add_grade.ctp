<?php if(!empty($gradeOptions)) { ?>

<div class="table_row">
	<div class="table_cell">
		<?php
		echo $this->Form->input('institution_site_programme_id', array(
			'label' => false,
			'div' => false,
			'url' => 'InstitutionSites/programmesGradeList',
			'options' => $programmeOptions,
			'default' => $selectedProgramme,
			'onchange' => 'objInstitutionSite.getGradeList(this)'
		));
		?>
	</div>
	<div class="table_cell">
		<?php 
		echo $this->Form->input(sprintf('%s.%d.%s', $model, $index, 'education_grade_id'), array(
			'label' => false,
			'div' => false,
			'class' => 'grades',
			'options' => $gradeOptions
		)); 
		?>
	</div>
	<div class="table_cell"><?php echo $this->Utility->getDeleteControl(); ?></div>
</div>

<?php } else { ?>

<span class="alert" type="<?php echo $this->Utility->alertType['error']; ?>"><?php echo __('No more grades available.'); ?></span>

<?php } ?>