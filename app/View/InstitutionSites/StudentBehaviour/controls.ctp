<div class="row page-controls">
	<div class="col-md-3">
		<?php
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
		echo $this->Form->input('institution_site_class_id', array(
			'label' => false,
			'div' => false,
			'class' => ' form-control',
			'default' => $selectedClass,
			'options' => $classOptions,
			'onchange' => 'jsForm.change(this)',
			'url' => "InstitutionSites/$model/show/" . $selectedYear
		));
		?>
	</div>
</div>
