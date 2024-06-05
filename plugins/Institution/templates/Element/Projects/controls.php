<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
		<?php
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->getParam('plugin'),
			    'controller' => $this->request->getParam('controller'),
			    'action' => $this->request->getParam('action'),
                '0' => 'index',
                '1' => $encodedQueryString,
			]);
			$template = $this->ControllerAction->getFormTemplate();
			$this->Form->templates($template);

				if (!empty($fundingSourceOptions)) {
                     echo $this->Form->input('infrastructure_project_funding_source_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $fundingSourceOptions,
                        'default' => $selectedFundingSource,
                        'url' => $baseUrl,
                        'data-named-key' => 'funding_source',
                        'data-named-group' => 'status'
                    ));
                }

                if (!empty($projectStatusesOptions)) {
                     echo $this->Form->input('status', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $projectStatusesOptions,
                        'default' => $selectedProjectStatuses,
                        'url' => $baseUrl,
                        'data-named-key' => 'status',
                        'data-named-group' => 'funding_source'
                    ));
                }

			
		?>
		</div>
	</div>	
