<?php if (!empty($modelOptions) || !empty($workflowOptions)) : ?>
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

				if (!empty($workflowOptions)) {
					echo $this->Form->input('workflow', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $workflowOptions,
						'default' => $selectedWorkflow,
						'url' => $baseUrl,
						'data-named-key' => 'workflow',
						'data-named-group' => 'model'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
