<div class="row page-controls">
	<div class="col-md-3">
		<?php
		echo $this->Form->input('school_year_id', array(
			'id' => 'SchoolYearId',
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
	<div class="row page-controls">
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
			'url' => $this->params['controller'] . '/' . $selectedGradeId
		));
		?>
	</div>
</div>
</div>