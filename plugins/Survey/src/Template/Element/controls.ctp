<?php if (!empty($statusOptions) || !empty($moduleOptions) || !empty($templateOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				$baseUrl = $this->Url->build([
					'plugin' => $this->request->params['plugin'],
				    'controller' => $this->request->params['controller'],
				    'action' => 'index'
				]);
				$template = $this->ControllerAction->getFormTemplate();
				$this->Form->templates($template);

				if (!empty($statusOptions)) {
					echo $this->Form->input('survey_status', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $statusOptions,
						'default' => $selectedStatus,
						'url' => $baseUrl,
						'data-named-key' => 'status'
					));
				}

				if (!empty($moduleOptions)) {
					echo $this->Form->input('survey_module', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $moduleOptions,
						'default' => $selectedModule,
						'url' => $baseUrl,
						'data-named-key' => 'module'
					));
				}

				if (!empty($templateOptions)) {
					echo $this->Form->input('survey_template', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $templateOptions,
						'default' => $selectedTemplate,
						'url' => $baseUrl,
						'data-named-key' => 'template'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
