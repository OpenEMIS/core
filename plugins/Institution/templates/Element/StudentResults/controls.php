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

			echo $this->Form->input('academic_period', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $periodOptions,
				'url' => $baseUrl,
				'data-named-key' => 'academic_period_id'
			));

			echo $this->Form->input('classes', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $sectionOptions,
				'url' => $baseUrl,
				'data-named-key' => 'class_id',
				'data-named-group' => 'academic_period_id'
			));

			echo $this->Form->input('classes', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $classOptions,
				'url' => $baseUrl,
				'data-named-key' => 'class_id',
				'data-named-group' => 'academic_period_id,class_id'
			));
		?>
	</div>
</div>
