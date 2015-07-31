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
					'url' => $baseUrl,
					'data-named-key' => 'period'
				));
			}

			if (!empty($gradeOptions)) {
				echo $this->Form->input('education_grade', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $gradeOptions,
					'url' => $baseUrl,
					'data-named-key' => 'grade',
					'data-named-group' => 'period'
				));
			}
		?>
	</div>
</div>
