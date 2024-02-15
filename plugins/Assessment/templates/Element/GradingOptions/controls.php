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

			echo $this->Form->input('grading_types', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $gradingTypeOptions,
				'url' => $baseUrl,
				'data-named-key' => 'grading_type_id'
			));
		?>
	</div>
</div>
