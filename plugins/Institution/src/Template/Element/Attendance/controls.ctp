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

			echo $this->Form->input('weeks', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $weekOptions,
				'url' => $baseUrl,
				'data-named-key' => 'week'
			));

			echo $this->Form->input('days', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $dayOptions,
				'default' => $selectedDay,
				'url' => $baseUrl,
				'data-named-key' => 'day'
			));

			echo $this->Form->input('sections', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $sectionOptions,
				'url' => $baseUrl,
				'data-named-key' => 'section_id'
			));
		?>
	</div>
</div>
