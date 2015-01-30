<div class="row page-controls">
	<?php
	echo $this->Form->input('academic_period_id', array(
		'class' => 'form-control',
		'label' => false,
		'options' => $academicPeriodOptions,
		'default' => $selectedAcademicPeriod,
		'div' => 'col-md-3',
		'url' => sprintf('%s/%s/index', $this->params['controller'], $model),
		'onchange' => 'jsForm.change(this)'
	));
	?>
</div>
