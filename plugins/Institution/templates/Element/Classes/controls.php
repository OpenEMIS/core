<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
		<?php
			$this->Form->unlockField('academic_period_id_');
			$this->Form->unlockField('education_grade_id_');
			
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->getParam('plugin'),
			    'controller' => $this->request->getParam('controller'),
			    'action' => $this->request->getParam('action'),
			    '0' => 'index',
				'1' => $encodedQueryString,
			]);
			$template = $this->ControllerAction->getFormTemplate();
			$this->Form->templates($template);

			if (!empty($academicPeriodOptions)) {
				echo $this->Form->input('academic_period_id_', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $academicPeriodOptions,
					'url' => $baseUrl,
					'data-named-key' => 'academic_period_id',
				));
			} ?>
		<?php	if (!empty($gradeOptions)) {
				echo $this->Form->input('education_grade_id_', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $gradeOptions,
					'default' => $selectedGrade,
					'url' => $baseUrl,
					'data-named-key' => 'education_grade_id',
					'data-named-group' => 'academic_period_id',
				));
			} ?>
	</div>
</div>
