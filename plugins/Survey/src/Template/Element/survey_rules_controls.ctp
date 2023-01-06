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
				
				if (!empty($surveyFormOptions)) 
				{
					echo $this->Form->input('survey_form', [
						'class' => 'form-control',
						'label' => false,
						'options' => $surveyFormOptions,
						'url' => $baseUrl,
						'data-named-key' => 'survey_form_id',
						'data-named-group' => 'section_id'
					]);
				}

				if (!empty($sectionOptions)) 
				{
					echo $this->Form->input('survey_section', [
						'class' => 'form-control',
						'label' => false,
						'options' => $sectionOptions,
						'url' => $baseUrl,
						'data-named-key' => 'section_id',
						'data-named-group' => 'survey_form_id'
					]);
				}


			?>
		</div>
	</div>
