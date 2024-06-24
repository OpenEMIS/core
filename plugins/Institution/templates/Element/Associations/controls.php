	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
		<?php
			$this->Form->unlockField('academic_period_id_');
			
			$baseUrl = $this->Url->build([
				'plugin' => $this->request->getParam('plugin'),
	            'controller' => $this->request->getParam('controller'),
	            'action' => $this->request->getParam('action'),
	            '0' => 'index',
'1' => $encodedQueryString,
			]);
			$template = $this->ControllerAction->getFormTemplate();
			$this->Form->templates($template); ?>
			<?php if (!empty($academicPeriodOptions)) { ?>
	            <?php echo $this->Form->input('academic_period_id_', array(
					'type' => 'select',
					'class' => 'form-control',
					'label' => false,
					'options' => $academicPeriodOptions,
					'url' => $baseUrl,
					'data-named-key' => 'academic_period_id',
				)); ?>
			<?php } ?>
		</div>
	</div>
