<div class="row page-controls">
	<div class="col-md-3">
		<?php
		echo $this->Form->input('academic_period_id', array(
			'id' => 'AcademicPeriodId',
			'label' => false,
			'div' => false,
			'class' => 'form-control',
			'options' => $academicPeriodOptions,
			'default' => $selectedAcademicPeriod,
			'onchange' => 'jsForm.change(this)',
			'url' => $this->params['controller'] . '/' . $url
		));
		?>
	</div>
</div>

