<?php if (!empty($periodOptions) || !empty($sectionOptions)) : ?>
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
					echo $this->Form->input('academic_period', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $periodOptions,
						'url' => $baseUrl,
						'data-named-key' => 'period_id'
					));
				}

				if (!empty($sectionOptions)) {
					echo $this->Form->input('sections', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $sectionOptions,
						'url' => $baseUrl,
						'data-named-key' => 'section_id',
						'data-named-group' => 'period_id'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
