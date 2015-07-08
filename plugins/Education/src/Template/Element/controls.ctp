<?php if (!empty($systemOptions) || !empty($levelOptions) || !empty($cycleOptions) || !empty($setupOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				$baseUrl = $this->Url->build([
					'plugin' => $this->request->params['plugin'],
				    'controller' => $this->request->params['controller'],
				    'action' => $this->request->params['action']
				]);
				$template = $this->ControllerAction->getFormTemplate();
				$this->Form->templates($template);

				if (!empty($systemOptions)) {
					echo $this->Form->input('systems', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $systemOptions,
						'default' => $selectedSystem,
						'url' => $baseUrl,
						'data-named-key' => 'system'
					));
				}

				if (!empty($levelOptions)) {
					echo $this->Form->input('levels', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $levelOptions,
						'default' => $selectedLevel,
						'url' => $baseUrl,
						'data-named-key' => 'level'
					));
				}

				if (!empty($cycleOptions)) {
					echo $this->Form->input('cycles', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $cycleOptions,
						'default' => $selectedCycle,
						'url' => $baseUrl,
						'data-named-key' => 'cycle',
						'data-named-group' => 'level'
					));
				}

				if (!empty($setupOptions)) {
					echo $this->Form->input('setups', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $setupOptions,
						'default' => $selectedSetup,
						'url' => $baseUrl,
						'data-named-key' => 'setup'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
