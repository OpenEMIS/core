<?php if (!empty($levelOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				if (!empty($levelOptions)) {
					$baseUrl = $this->Url->build([
						'plugin' => $this->request->params['plugin'],
					    'controller' => $this->request->params['controller'],
					    'action' => $this->request->params['action']
					]);

					echo $this->Form->input('survey_module', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $levelOptions,
						'default' => 'level=' . $selectedLevel,
						'url' => $baseUrl,
						'onchange' => 'jsForm.change(this);'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
