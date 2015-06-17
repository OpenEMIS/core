<?php if (!empty($moduleOptions) || !empty($templateOptions) || !empty($statusOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				if (!empty($moduleOptions)) {
					$baseUrl = $this->Url->build([
						'plugin' => $this->request->params['plugin'],
					    'controller' => $this->request->params['controller'],
					    'action' => 'index'
					]);

					echo $this->Form->input('survey_module', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $moduleOptions,
						'default' => 'module=' . $selectedModule,
						'url' => $baseUrl,
						'onchange' => 'jsForm.change(this);'
					));
				}

				if (!empty($templateOptions)) {
					$baseUrl = $this->Url->build([
						'plugin' => $this->request->params['plugin'],
					    'controller' => $this->request->params['controller'],
					    'action' => 'index',
					    'module' => $selectedModule
					]);

					echo $this->Form->input('survey_module', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $templateOptions,
						'default' => 'template=' . $selectedTemplate,
						'url' => $baseUrl,
						'onchange' => 'jsForm.change(this);'
					));
				}

				if (!empty($statusOptions)) {
					$selectedStatus = 0;
					$baseUrl = $this->Url->build([
						'plugin' => $this->request->params['plugin'],
					    'controller' => $this->request->params['controller'],
					    'action' => 'index',
					    'module' => $selectedModule
					]);

					echo $this->Form->input('survey_status', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $statusOptions,
						'default' => 'status:' . $selectedStatus,
						'url' => $baseUrl,
						'onchange' => 'jsForm.change(this);'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
