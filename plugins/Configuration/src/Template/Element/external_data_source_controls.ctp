
<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
        <h1><?php print_r($field_type) ?></h1>

		<?php
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->params['plugin'],
			    'controller' => $this->request->params['controller'],
			    'action' => $this->request->params['action'],
			]);
			$template = $this->ControllerAction->getFormTemplate();

			$this->Form->templates($template);
			echo $this->Form->input('field_option', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $typeOptions,
				'url' => $baseUrl,
				'data-named-key' => 'type'
			));
			//POCOR-7981 Starts add if condition
			if(!empty($extraExternalDataSourceTypeOptions)
            ){
				echo $this->Form->input('extra_external_data_source_type', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $extraExternalDataSourceTypeOptions,
					'url' => $baseUrl,
					'data-named-key' => 'extra_external_data_source_type',
					'data-named-group' => 'type'
				));
			}//POCOR-7981 ends
		?>
	</div>
</div>
