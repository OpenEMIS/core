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

            if (!empty($periodOptions)) {
                echo $this->Form->input('academic_period_id', array(
                   'type' => 'select',
                   'class' => 'form-control',
                   'label' => false,
                   'options' => $periodOptions,
                   'default' => $selectedPeriod,
                   'url' => $baseUrl,
                   'data-named-key' => 'period',
                   'data-named-group' => 'trip_types'
               ));
            }

            if (!empty($tripTypeOptions)) {
                    echo $this->Form->input('trip_type_id', array(
                    'type' => 'select',
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $tripTypeOptions,
                    'default' => $selectedtripTypes,
                    'url' => $baseUrl,
                    'data-named-key' => 'trip_types',
                    'data-named-group' => 'period'
                ));
            }

			
		?>
		</div>
	</div>	
