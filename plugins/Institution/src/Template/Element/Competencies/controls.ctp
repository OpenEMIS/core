<?php if (!empty($periodOptions) || !empty($competencyOptions)) : ?>
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
						'data-named-key' => 'period'
					));
				}

				if (!empty($competencyOptions)) {
					echo $this->Form->input('assessment', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $competencyOptions,
						'default' => $selectedCompetency,
						'url' => $baseUrl,
						'data-named-key' => 'competency',
						'data-named-group' => 'period'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
