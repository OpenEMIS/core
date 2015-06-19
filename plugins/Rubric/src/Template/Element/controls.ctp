<?php if (!empty($templateOptions) || !empty($sectionOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				$baseUrl = $this->Url->build([
					'plugin' => $this->request->params['plugin'],
				    'controller' => $this->request->params['controller'],
				    'action' => $this->request->params['action'],
				]);

				if (!empty($templateOptions)) {
					echo $this->Form->input('rubric_template', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $templateOptions,
						'default' => $selectedTemplate,
						'url' => $baseUrl,
						'data-named-key' => 'template'
					));
				}

				if (!empty($sectionOptions)) {
					echo $this->Form->input('rubric_section', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $sectionOptions,
						'default' => $selectedSection,
						'url' => $baseUrl,
						'data-named-key' => 'section'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
