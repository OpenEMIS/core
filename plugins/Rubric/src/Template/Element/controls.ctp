<?php if (!empty($templateOptions) || !empty($sectionOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				if (!empty($templateOptions)) {
					$baseUrl = $this->Url->build([
						'plugin' => $this->request->params['plugin'],
					    'controller' => $this->request->params['controller'],
					    'action' => $this->request->params['action']
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

				if (!empty($sectionOptions)) {
					$baseUrl = $this->Url->build([
						'plugin' => $this->request->params['plugin'],
					    'controller' => $this->request->params['controller'],
					    'action' => $this->request->params['action'],
					    'template' => $selectedTemplate
					]);

					echo $this->Form->input('survey_module', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $sectionOptions,
						'default' => 'section=' . $selectedSection,
						'url' => $baseUrl,
						'onchange' => 'jsForm.change(this);'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
