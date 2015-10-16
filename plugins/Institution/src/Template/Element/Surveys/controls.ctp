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

		if (!empty($statusOptions)) {
			echo $this->Form->input('status', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $statusOptions,
				'url' => $baseUrl,
				'data-named-key' => 'status',
			));
		}
	?>
	</div>
</div>
