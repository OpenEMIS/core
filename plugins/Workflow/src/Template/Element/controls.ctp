<?php if (!empty($workflowOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				if (!empty($workflowOptions)) {
					$baseUrl = $this->Url->build([
						'plugin' => $this->request->params['plugin'],
					    'controller' => $this->request->params['controller'],
					    'action' => $this->request->params['action']
					]);

					echo $this->Form->input('workflow', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $workflowOptions,
						'default' => 'workflow=' . $selectedWorkflow,
						'url' => $baseUrl,
						'onchange' => 'jsForm.change(this);'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
