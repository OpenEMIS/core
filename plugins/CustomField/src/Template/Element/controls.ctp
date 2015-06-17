<?php if (!empty($moduleOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				if (!empty($moduleOptions)) {
					$baseUrl = $this->Url->build([
						'plugin' => $this->request->params['plugin'],
					    'controller' => $this->request->params['controller'],
					    'action' => $this->request->params['action'],
					]);

					echo $this->Form->input('custom_module', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $moduleOptions,
						'default' => 'module=' . $selectedModule,
						'url' => $baseUrl,
						'onchange' => 'jsForm.change(this);'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
