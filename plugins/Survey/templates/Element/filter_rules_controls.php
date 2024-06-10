	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				$baseUrl = $this->Url->build([
					'plugin' => $this->request->getParam('plugin'),
				    'controller' => $this->request->getParam('controller'),
				    'action' => $this->request->getParam('action')
				]);
				$template = $this->ControllerAction->getFormTemplate();
				$this->Form->templates($template);

				if (!empty($moduleOptions)) {
					echo $this->Form->input('survey_module', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $moduleOptions,
						'default' => $selectedModule,
						'url' => $baseUrl,
						'data-named-key' => 'survey_module_id',
						'data-named-group' => 'survey_form_id'
					));
				}
				
				if (!empty($surveyFormOptions)) 
				{
					echo $this->Form->input('survey_form', [
						'class' => 'form-control',
						'label' => false,
						'options' => $surveyFormOptions,
						'url' => $baseUrl,
						'data-named-key' => 'survey_form_id',
						'data-named-group' => 'survey_module_id'
					]);
				}

			?>
		</div>
	</div>
