<?php if (!empty($statusOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				$baseUrl = $this->Url->build([
					'plugin' => $this->request->params['plugin'],
				    'controller' => $this->request->params['controller'],
				    'action' => $this->request->params['action'],
				    '0' => 'index'
				]);
				$template = $this->ControllerAction->getFormTemplate();
				$this->Form->templates($template);

				if (!empty($academicPeriodOptions)) {
					echo $this->Form->input('academic_period', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $academicPeriodOptions,
						'url' => $baseUrl,
						'data-named-key' => 'academic_period_id',
						'data-named-group' => 'status_id,education_grade_id'
					));
				}

				if (!empty($educationGradesOptions)) {
					echo $this->Form->input('education_grade', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $educationGradesOptions,
						'url' => $baseUrl,
						'data-named-key' => 'education_grade_id',
						'data-named-group' => 'status_id,academic_period_id'
					));
				}

				if (!empty($statusOptions)) {
					echo $this->Form->input('student_status', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $statusOptions,
						'url' => $baseUrl,
						'data-named-key' => 'status_id',
						'data-named-group' => 'academic_period_id,education_grade_id'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
