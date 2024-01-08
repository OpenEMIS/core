	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
		<?php
			$this->Form->unlockField('academic_period_id_');
			
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->params['plugin'],
			    'controller' => $this->request->params['controller'],
			    'action' => $this->request->params['action']
			]);
			$template = $this->ControllerAction->getFormTemplate();
			$this->Form->templates($template);

			if (!empty($academicPeriodOptions)
                && !empty($selectedAcademicPeriod) // POCOR-7988
            ) {
				echo $this->Form->input('academic_period_id_', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $academicPeriodOptions,
                    'default' => $selectedAcademicPeriod, // POCOR-7988
					'url' => $baseUrl,
					'data-named-key' => 'academic_period_id',
				));
			}

		?>
		</div>
	</div>
