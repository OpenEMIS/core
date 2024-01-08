<?php if (!empty($statusOptions) || !empty($moduleOptions) || !empty($formOptions)) : ?>
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

				if (!empty($statusOptions)) {
					echo $this->Form->input('survey_status', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $statusOptions,
						'default' => $selectedStatus,
						'url' => $baseUrl,
						'data-named-key' => 'status',
						'data-named-group' => 'module,form'
					));
				}

				if (!empty($moduleOptions)) {
					echo $this->Form->input('survey_module', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $moduleOptions,
						'default' => $selectedModule,
						'url' => $baseUrl,
						'data-named-key' => 'module',
						'data-named-group' => 'status'
					));
				}

				if (!empty($formOptions)) {
					echo $this->Form->input('survey_form', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $formOptions,
						'default' => $selectedForm,
						'url' => $baseUrl,
						'data-named-key' => 'form',
						'data-named-group' => 'status,module'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
