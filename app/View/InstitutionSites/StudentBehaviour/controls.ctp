<div class="row page-controls">
	<div class="col-md-3">
		<?php
		if (empty($academicPeriodOptions)) {
			array_push($academicPeriodOptions, $this->Label->get('general.noData'));
		}
		echo $this->Form->input('academic_period_id', array(
			'label' => false,
			'div' => false,
			'class' => ' form-control',
			'default' => $selectedAcademicPeriod,
			'options' => $academicPeriodOptions,
			'onchange' => 'jsForm.change(this)',
			'url' => "InstitutionSites/$model/show"
		));
		?>
	</div>

	<div class="col-md-3">
		<?php
		if (empty($sectionOptions)) {
			array_push($sectionOptions, $this->Label->get('general.noData'));
		}
		echo $this->Form->input('institution_site_section_id', array(
			'label' => false,
			'div' => false,
			'class' => ' form-control',
			'default' => $selectedSection,
			'options' => $sectionOptions,
			'onchange' => 'jsForm.change(this)',
			'url' => "InstitutionSites/$model/show/" . $selectedAcademicPeriod
		));
		?>
	</div>
</div>
