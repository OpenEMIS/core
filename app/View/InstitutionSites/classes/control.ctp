<div class="row page-controls">
	<div class="col-md-3">
		<?php
		echo $this->Form->input('school_year_id', array(
			'id' => 'SchoolYearId',
			'label' => false,
			'div' => false,
			'class' => 'form-control',
			'options' => $yearOptions,
			'default' => $selectedYear,
			'onchange' => 'jsForm.change(this)',
			'url' => $this->params['controller'] . '/classes'
		));
		?>
	</div>
	<div class="col-md-3">
		<?php
		echo $this->Form->input('institution_site_section_id', array(
			'id' => 'InstitutionSiteSectionId',
			'label' => false,
			'div' => false,
			'class' => 'form-control',
			'options' => $sectionOptions,
			'default' => $selectedSection,
			'onchange' => 'jsForm.change(this)',
			'url' => $this->params['controller'] . '/classes/' . $selectedYear
		));
		?>
	</div>
</div>
