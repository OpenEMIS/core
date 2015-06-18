<?php if (!empty($statusOptions) || !empty($moduleOptions) || !empty($templateOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				if (!empty($statusOptions)) {
					$baseUrl = $this->Url->build([
						'plugin' => $this->request->params['plugin'],
					    'controller' => $this->request->params['controller'],
					    'action' => 'index'
					]);

					echo $this->Form->input('survey_status', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $statusOptions,
						'default' => 'status=' . $selectedStatus,
						'url' => $baseUrl,
						'onchange' => 'jsForm.change(this);'
					));
				}

				if (!empty($moduleOptions)) {
					$baseUrl = $this->Url->build([
						'plugin' => $this->request->params['plugin'],
					    'controller' => $this->request->params['controller'],
					    'action' => 'index',
					    'status' => $selectedStatus
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
					    'status' => $selectedStatus,
					    'module' => $selectedModule
					]);

					echo $this->Form->input('survey_template', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $templateOptions,
						'default' => 'template=' . $selectedTemplate,
						'url' => $baseUrl,
						'onchange' => 'jsForm.change(this);'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
