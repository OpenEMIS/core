<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
		<?php
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->getParam('plugin'),
			    'controller' => $this->request->getParam('controller'),
			    'action' => $this->request->getParam('action'),
			    '0' => 'index'
			]);
			$template = $this->ControllerAction->getFormTemplate();
			$this->Form->templates($template); ?>

			<?php	if (!empty($periodOptions)) { ?>
				<div class="input select required">
	                <div class="input-select-wrapper">
					<?php	echo $this->Form->input('period_id', [
						'type' => 'select',
						'class' => 'form-control',
						'label' => false,
						'options' => $periodOptions,
						'url' => $baseUrl,
						'data-named-key' => 'academic_period_id',
						'data-named-group' => 'position, staff_status_id'
					]); ?>
					</div>
				</div>
			<?php	} ?>
			<?php	if (!empty($positionOptions)) { ?>
				<div class="input select required">
	                <div class="input-select-wrapper">
					<?php	echo $this->Form->input('position', array(
						'type' => 'select',
						'class' => 'form-control',
						'label' => false,
						'options' => $positionOptions,
						'url' => $baseUrl,
						'data-named-key' => 'position',
						'data-named-group' => 'academic_period_id, staff_status_id'
					)); ?>
					</div>
				</div>
			<?php	} ?>

			<?php	if (!empty($statusOptions)) { ?>
				<div class="input select required">
	                <div class="input-select-wrapper">
					<?php	echo $this->Form->input('status', array(
						'type' => 'select',
						'class' => 'form-control',
						'label' => false,
						'options' => $statusOptions,
						'url' => $baseUrl,
						'data-named-key' => 'staff_status_id',
						'data-named-group' => 'academic_period_id, position'
					)); ?>
					</div>
				</div>
			<?php	} ?>
		?>
	</div>
</div>
