<?= $this->Html->script('Configuration.authentication_config', ['block' => true]); ?>

<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
		<?php
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->getParam('plugin'),
			    'controller' => $this->request->getParam('controller'),
			    'action' => $this->request->getParam('action'),
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
			//POCOR-7156 Starts add if condition
			//POCOR-8996 start
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
			//POCOR-8996 end
		?>
	</div>
</div>
