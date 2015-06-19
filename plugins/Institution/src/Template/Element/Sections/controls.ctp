	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
		<?php
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->params['plugin'],
			    'controller' => $this->request->params['controller'],
			    'action' => $this->request->params['action']
			]);

			if (!empty($academicPeriodOptions)) {
				echo $this->Form->input('academic_period_', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $academicPeriodOptions,
					'default' => $selectedAcademicPeriod,
					'url' => $baseUrl,
					'data-named-key' => 'academic_period',
				));
			}

			if (!empty($gradeOptions)) {
				echo $this->Form->input('grade_', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $gradeOptions,
					'default' => $selectedGrade,
					'url' => $baseUrl,
					'data-named-key' => 'grade',
				));
			}

		?>
		</div>
	</div>
