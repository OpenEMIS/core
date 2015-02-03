<div class="row page-controls">
	<div class="col-md-3">
		<?php
		echo $this->Form->input('academic_period_id', array(
			'label' => false,
			'div' => false,
			'class' => 'form-control',
			'options' => $periodOptions,
			'default' => $selectedPeriod,
			'onchange' => 'jsForm.change(this)',
			'url' => $this->params['controller'] . '/' . $url
		));
		?>
	</div>
	<div class="col-md-3">
		<?php
		echo $this->Form->input('education_grade_id', array(
			'id' => 'EducationGradeId',
			'label' => false,
			'div' => false,
			'class' => 'form-control',
			'options' => $gradeOptions,
			'default' => $selectedGradeId,
			'onchange' => 'jsForm.change(this)',
			'url' => $this->params['controller'] . '/' . $url . '/' . $selectedPeriod
		));
		?>
	</div>
</div>
