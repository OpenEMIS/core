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
			echo $this->Form->input('event_key', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $eventKeyOptions,
				'url' => $baseUrl,
				'data-named-key' => 'event_key',
				'data-named-group' => 'type'
			));
		?>
	</div>
</div>
