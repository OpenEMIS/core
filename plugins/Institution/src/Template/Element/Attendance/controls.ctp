<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
		<?php
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->params['plugin'],
			    'controller' => $this->request->params['controller'],
			    'action' => $this->request->params['action'],
			]);
	
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
				'default' => $selectedWeek,
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
		?>
	</div>
</div>
