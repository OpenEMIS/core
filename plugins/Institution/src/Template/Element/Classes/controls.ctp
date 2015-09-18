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

			if (!empty($academicPeriodOptions)) {
				echo $this->Form->input('academic_period_id_', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $academicPeriodOptions,
					'url' => $baseUrl,
					'data-named-key' => 'academic_period_id',
				));
			}

			if (!empty($sectionOptions)) {
				echo $this->Form->input('section_id', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $sectionOptions,
					'url' => $baseUrl,
					'data-named-key' => 'section_id',
					'data-named-group' => 'academic_period_id',
				));
			}

		?>
		</div>
	</div>
