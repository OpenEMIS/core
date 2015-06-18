<?php if (!empty($workflowOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				$baseUrl = $this->Url->build([
					'plugin' => $this->request->params['plugin'],
				    'controller' => $this->request->params['controller'],
				    'action' => $this->request->params['action']
				]);

				if (!empty($workflowOptions)) {
					echo $this->Form->input('workflow', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $workflowOptions,
						'default' => $selectedWorkflow,
						'url' => $baseUrl,
						'data-named-key' => 'workflow'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
