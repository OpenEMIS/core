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
						'url' => $baseUrl,
						'data-named-key' => 'survey_module_id',
						'data-named-group' => 'survey_form_id,survey_filter_id'
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
						'data-named-group' => 'survey_module_id,survey_filter_id'
					]);
				}

				if (!empty($surveyFilterOptions)) 
				{
					echo $this->Form->input('survey_filter', [
						'class' => 'form-control',
						'label' => false,
						'options' => $surveyFilterOptions,
						'url' => $baseUrl,
						'data-named-key' => 'survey_filter_id',
						'data-named-group' => 'survey_form_id,survey_module_id'
					]);
				}

			?>
		</div>
	</div>
