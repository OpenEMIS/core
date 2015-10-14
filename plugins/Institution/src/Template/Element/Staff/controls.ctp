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

			if (!empty($periodOptions)) {
				echo $this->Form->input('academic_period_id', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $periodOptions,
					'url' => $baseUrl,
					'data-named-key' => 'academic_period_id',
					'data-named-group' => 'position'
				));
			}

			if (!empty($positionOptions)) {
				echo $this->Form->input('position', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $positionOptions,
					'url' => $baseUrl,
					'data-named-key' => 'position',
					'data-named-group' => 'academic_period_id'
				));
			}

			// if (!empty($statusOptions)) {
			// 	echo $this->Form->input('status', array(
			// 		'class' => 'form-control',
			// 		'label' => false,
			// 		'options' => $statusOptions,
			// 		'url' => $baseUrl,
			// 		'data-named-key' => 'status_id',
			// 		'data-named-group' => 'period, position'
			// 	));
			// }
		?>
	</div>
</div>
