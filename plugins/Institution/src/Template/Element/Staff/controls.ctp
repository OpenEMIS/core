<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
		<?php
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->params['plugin'],
			    'controller' => $this->request->params['controller'],
			    'action' => $this->request->params['action'],
			    '0' => 'index'
			]);
			$template = $this->ControllerAction->getFormTemplate();
			$this->Form->templates($template);

			if (!empty($periodOptions)) {
				echo $this->Form->input('period_id', [
					'class' => 'form-control',
					'label' => false,
					'options' => $periodOptions,
					'url' => $baseUrl,
					'data-named-key' => 'academic_period_id',
					'data-named-group' => 'position, staff_status_id'
				]);
			}

			if (!empty($positionOptions)) {
				echo $this->Form->input('position', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $positionOptions,
					'url' => $baseUrl,
					'data-named-key' => 'position',
					'data-named-group' => 'academic_period_id, staff_status_id'
				));
			}

			if (!empty($statusOptions)) {
				echo $this->Form->input('status', array(
					'class' => 'form-control',
					'label' => false,
					'options' => $statusOptions,
					'url' => $baseUrl,
					'data-named-key' => 'staff_status_id',
					'data-named-group' => 'academic_period_id, position'
				));
			}
		?>
	</div>
</div>
