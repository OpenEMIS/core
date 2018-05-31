<?php if (!empty($featureOptions) || !empty($academicPeriodOptions) || !empty($educationGradesOptions) || !empty($institutionClassOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				$baseUrl = $this->Url->build([
					'plugin' => $this->request->params['plugin'],
				    'controller' => $this->request->params['controller'],
				    'action' => $this->request->params['action']
				]);
				$template = $this->ControllerAction->getFormTemplate();
				$this->Form->templates($template);

				if (!empty($featureOptions)) {
					echo $this->Form->input('feature', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $featureOptions,
						'url' => $baseUrl,
						'data-named-key' => 'feature'	
					));
				}

				if (!empty($academicPeriodOptions)) {
					echo $this->Form->input('feature', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $academicPeriodOptions,
						'url' => $baseUrl,
						'data-named-key' => 'academic_period_id',
						'data-named-group' => 'feature'
					));
				}

				if (!empty($educationGradesOptions)) {
					echo $this->Form->input('education_grade', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $educationGradesOptions,
						'url' => $baseUrl,
						'data-named-key' => 'education_grade_id',
						'data-named-group' => 'feature,academic_period_id'
					));
				}

				if (!empty($institutionClassOptions)) {
					echo $this->Form->input('education_grade', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $institutionClassOptions,
						'url' => $baseUrl,
						'data-named-key' => 'institution_class_id',
						'data-named-group' => 'feature,academic_period_id, education_grade_id'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
