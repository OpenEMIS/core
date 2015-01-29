<div class="row page-controls">
	<div class="col-md-3">
		<?php
		echo $this->Form->input('academic_period_id', array(
			'id' => 'AcademicPeriodId',
			'label' => false,
			'div' => false,
			'class' => 'form-control',
			'options' => $periodOptions,
			'default' => $selectedPeriod,
			'onchange' => 'jsForm.change(this)',
			'url' => $this->params['controller'] . '/InstitutionSiteClass/index'
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
			'url' => $this->params['controller'] . '/InstitutionSiteClass/index/' . $selectedPeriod
		));
		?>
	</div>
</div>
