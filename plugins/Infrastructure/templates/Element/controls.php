<?php if (!empty($levelOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				$baseUrl = $this->Url->build([
					'plugin' => $this->request->getParam('plugin'),
				    'controller' => $this->request->getParam('controller'),
				    'action' => $this->request->getParam('action'),
				]);
				$template = $this->ControllerAction->getFormTemplate();
				$this->Form->templates($template);

				if (!empty($levelOptions)) {
					echo $this->Form->input('infrastructure_level', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $levelOptions,
						'default' => $selectedLevel,
						'url' => $baseUrl,
						'data-named-key' => 'level'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
