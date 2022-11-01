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

			echo $this->Form->input('period', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $periodOptions,
				'url' => $baseUrl,
				'data-named-key' => 'period',
				'default' => $selectedPeriodOption,
			));
		?>
		</div>
	</div>	
