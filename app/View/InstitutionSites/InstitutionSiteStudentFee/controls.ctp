<div class="row page-controls">
	<?php
	echo $this->Form->input('academic_period_id', array(
		'class' => 'form-control',
		'label' => false,
		'options' => $academicPeriodOptions,
		'default' => $selectedAcademicPeriod,
		'div' => 'col-md-4',
		'url' => sprintf('%s/%s/index', $this->params['controller'], $model),
		'onchange' => 'jsForm.change(this)'
	));
	?>
</div>

<div class="row page-controls">
	<?php
	echo $this->Form->input('education_grade_id', array(
		'class' => 'form-control',
		'label' => false,
		'options' => $gradeOptions,
		'default' => $selectedGrade,
		'div' => 'col-md-4',
		'url' => sprintf('%s/%s/index/%d', $this->params['controller'], $model, $selectedAcademicPeriod),
		'onchange' => 'jsForm.change(this)'
	));
	?>
</div>
