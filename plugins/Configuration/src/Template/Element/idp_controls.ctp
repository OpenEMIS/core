<?= $this->Html->script('Configuration.authentication_config', ['block' => true]); ?>

<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
		<?php
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->params['plugin'],
			    'controller' => $this->request->params['controller'],
			    'action' => $this->request->params['action'],
			]);
			$template = $this->ControllerAction->getFormTemplate();
			$this->Form->templates($template);
			//echo"<pre>"; print_r($this->request); die;
			echo $this->Form->input('field_option', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $typeOptions,
				'url' => $baseUrl,
				'data-named-key' => 'type'
			));
			//POCOR-7156 Starts add if condition
			if(!empty($authenticationTypeOptions)){
				echo $this->Form->input('authentication_type', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $authenticationTypeOptions,
					'url' => $baseUrl,
					'data-named-key' => 'authentication_type',
					'data-named-group' => 'type'
				));
			}//POCOR-7156 ends
		?>
	</div>
</div>
