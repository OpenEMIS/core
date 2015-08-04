<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
		<?php
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->params['plugin'],
			    'controller' => $this->request->params['controller'],
			    'action' => $this->request->params['action'],
			]);

			$compileUrl = $this->Url->build([
				'plugin' => $this->request->params['plugin'],
			    'controller' => $this->request->params['controller'],
			    'action' => 'compile',
			]);
			$template = $this->ControllerAction->getFormTemplate();
			$this->Form->templates($template);

			echo $this->Form->input('translation', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $localeOptions,
				'default' => $selectedOption,
				'url' => $baseUrl,
				'compile-url' => $compileUrl,
				'data-named-key' => 'translations_id'
			));
		?>
	</div>
</div>
