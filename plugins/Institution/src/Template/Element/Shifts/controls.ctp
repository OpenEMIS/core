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

			echo $this->Form->input('toggle_', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $toggleOptions,
				'url' => $baseUrl,
				'data-named-key' => 'toggle',
				'default' => $selectedToggleOption,
			));
		?>
		</div>
	</div>	
