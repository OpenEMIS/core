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

				if (!empty($needTypeOptions)) {
                     echo $this->Form->input('infrastructure_need_type_id', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $needTypeOptions,
                        'default' => $selectedNeedTypes,
                        'url' => $baseUrl,
                        'data-named-key' => 'need_types',
                        'data-named-group' => 'priority'
                    ));
                }

                if (!empty($needPrioritiesOptions)) {
                     echo $this->Form->input('priority', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $needPrioritiesOptions,
                        'default' => $selectedNeedPriorities,
                        'url' => $baseUrl,
                        'data-named-key' => 'priority',
                        'data-named-group' => 'need_types'
                    ));
                }

			
		?>
		</div>
	</div>	
