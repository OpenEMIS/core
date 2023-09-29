<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
		<?php
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->getParam('plugin'),
			    'controller' => $this->request->getParam('controller'),
			    'action' => $this->request->getParam('action'),
			]);
			$template = $this->ControllerAction->getFormTemplate();
			$this->Form->templates($template); ?>

		<?php	if (!empty($periodOptions)) { ?>
			<div class="input select required">
                <div class="input-select-wrapper">
				<?php	echo $this->Form->input('academic_period', array(
					'type' => 'select',
					'class' => 'form-control',
					'label' => false,
					'options' => $periodOptions,
					'url' => $baseUrl,
					'data-named-key' => 'academic_period_id'
				)); ?>
				</div>
			</div>
		<?php	} ?>

		<?php	if (!empty($classOptions)) { ?>
			<div class="input select required">
                <div class="input-select-wrapper">
				<?php	echo $this->Form->input('class', array(
					'type' => 'select',
					'class' => 'form-control',
					'label' => false,
					'options' => $classOptions,
					'url' => $baseUrl,
					'data-named-key' => 'class_id',
					'data-named-group' => 'academic_period_id'
				)); ?>
				</div>
			</div>
		<?php	} ?>

		<?php	if (!empty($categories)) { ?>
			<div class="input select required">
                <div class="input-select-wrapper">
				<?php echo $this->Form->input('class', array(
					'type' => 'select',
					'class' => 'form-control',
					'label' => false,
					'options' => $categories,
					'url' => $baseUrl,
					'data-named-key' => 'category_id',
				)); ?>
				</div>
			</div>
		<?php	} ?>
	</div>
</div>
