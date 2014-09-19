<div class="row page-controls">
	<?php
	echo $this->Form->input('school_year_id', array(
		'class' => 'form-control',
		'label' => false,
		'options' => $yearOptions,
		'default' => $selectedYear,
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
		'url' => sprintf('%s/%s/index/%d', $this->params['controller'], $model, $selectedYear),
		'onchange' => 'jsForm.change(this)'
	));
	?>
</div>
