<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper">
		<?php
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->getParam('plugin'),
			    'controller' => $this->request->getParam('controller'),
			    'action' => $this->request->getParam('action'),
			    '0' => 'index',
				'1' => $encodedQueryString,
			]);
			$template = $this->ControllerAction->getFormTemplate();
			$this->Form->templates($template); ?>
		<?php	if (!empty($periodOptions)) { ?>
				<?php	echo $this->Form->input('academic_period', array(
					'type' => 'select',
					'class' => 'form-control',
					'label' => false,
					'options' => $periodOptions,
					'url' => $baseUrl,
					'data-named-key' => 'academic_period_id'
				)); ?>
		<?php	} ?>

		<?php	if (!empty($classOptions)) { ?>
				<?php	echo $this->Form->input('class', array(
					'type' => 'select',
					'class' => 'form-control',
					'label' => false,
					'options' => $classOptions,
					'url' => $baseUrl,
					'data-named-key' => 'class_id',
					'data-named-group' => 'academic_period_id'
				)); ?>
				
		<?php	} ?>

		<?php	if (!empty($categories)) { ?>
				<?php echo $this->Form->input('category', array(
					'type' => 'select',
					'class' => 'form-control',
					'label' => false,
					'options' => $categories,
					'url' => $baseUrl,
					'data-named-key' => 'category_id',
					'data-named-group' => 'academic_period_id,class_id'
				)); ?>
				
		<?php	} ?>
	</div>
</div>
