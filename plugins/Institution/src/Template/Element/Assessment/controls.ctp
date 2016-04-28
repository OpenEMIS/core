<?php if (!empty($periodOptions) || !empty($assessmentOptions)) : ?>
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

				if (!empty($periodOptions)) {
					echo $this->Form->input('academic_period', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $periodOptions,
						'default' => $selectedPeriod,
						'url' => $baseUrl,
						'data-named-key' => 'academic_period_id'
					));
				}

				if (!empty($assessmentOptions)) {
					echo $this->Form->input('assessment', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $assessmentOptions,
						'default' => $selectedAssessment,
						'url' => $baseUrl,
						'data-named-key' => 'assessment_id',
						'data-named-group' => 'academic_period_id'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
