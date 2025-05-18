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

				if (!empty($periodOptions)) {
                     echo $this->Form->input('academic_period_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $periodOptions,
                        'default' => $selectedPeriod,
                        'url' => $baseUrl,
                        'data-named-key' => 'period'
                    ));
                }

                if (!empty($mealOptions)) {
                     echo $this->Form->input('meal_id', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $mealOptions,
                        'default' => $selectedMeal,
                        'url' => $baseUrl,
                        'data-named-key' => 'meal'
                    ));
                }

			
		?>
		</div>
	</div>	
