<div class="row page-controls">
	<div class="col-md-3">
		<?php
		if (empty($yearOptions)) {
			array_push($yearOptions, $this->Label->get('general.noData'));
		}
		echo $this->Form->input('school_year_id', array(
			'label' => false,
			'div' => false,
			'class' => ' form-control',
			'default' => $selectedYear,
			'options' => $yearOptions,
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
			'url' => "InstitutionSites/$model/show/" . $selectedYear
		));
		?>
	</div>
</div>
