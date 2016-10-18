<?php if (!empty($modelOptions) || !empty($workflowOptions) || !empty($workflowStepOptions)) : ?>
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
					echo $this->Form->input('workflow_model', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $modelOptions,
						'url' => $baseUrl,
						'data-named-key' => 'model'
					));
				}

				if (!empty($workflowOptions)) {
					echo $this->Form->input('workflow', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $workflowOptions,
						'url' => $baseUrl,
						'data-named-key' => 'workflow',
						'data-named-group' => 'model'
					));
				}

				if (!empty($workflowStepOptions)) {
					echo $this->Form->input('workflow_step', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $workflowStepOptions,
						'url' => $baseUrl,
						'data-named-key' => 'workflow_step',
						'data-named-group' => 'model,workflow'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
