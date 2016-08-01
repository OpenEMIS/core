<?php if (!empty($modelOptions)) : ?>
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

				if (!empty($modelOptions)) {
					echo $this->Form->input('workflow', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $modelOptions,
						'default' => $selectedModel,
						'url' => $baseUrl,
						'data-named-key' => 'model'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
