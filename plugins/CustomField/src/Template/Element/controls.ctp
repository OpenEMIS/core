<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
		<?php
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->params['plugin'],
			    'controller' => $this->request->params['controller'],
			    'action' => $this->request->params['action'],
			]);
			$template = $this->ControllerAction->getFormTemplate();
			$this->Form->templates($template);

			if (!empty($moduleOptions)) {
				echo $this->Form->input('custom_module', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $moduleOptions,
					'url' => $baseUrl,
					'data-named-key' => 'module'
				));
			}
		?>
	</div>
</div>
