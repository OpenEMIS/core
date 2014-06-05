<?php if(!empty($gradeOptions)) { ?>

<tr>
	<td class="table_cell">
		<?php
		echo $this->Form->input('institution_site_programme_id', array(
			'label' => false,
			'div' => false,
			'class' => 'form-control',
			'url' => 'InstitutionSites/programmesGradeList',
			'options' => $programmeOptions,
			'default' => $selectedProgramme,
			'onchange' => 'objInstitutionSite.getGradeList(this)'
		));
		?>
	</td>
	<td class="table_cell">
		<?php 
		echo $this->Form->input(sprintf('%s.%d.%s', $model, $index, 'education_grade_id'), array(
			'label' => false,
			'div' => false,
			'class' => 'grades form-control',
			'options' => $gradeOptions
		)); 
		?>
	</td>
	<td class="table_cell"><?php echo $this->Utility->getDeleteControl(); ?></td>
</tr>

<?php } else { ?>

<span class="alert" type="<?php echo $this->Utility->alertType['error']; ?>"><?php echo __('No more grades available.'); ?></span>

<?php } ?>