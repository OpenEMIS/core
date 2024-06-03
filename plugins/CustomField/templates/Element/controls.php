<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
		<?php
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->getParam('plugin'),
			    'controller' => $this->request->getParam('controller'),
			    'action' => $this->request->getParam('action'),
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
