<?php if (!empty($countryOptions)) : ?>
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

				if (!empty($countryOptions)) {
					echo $this->Form->input('country', array(
						'class' => 'form-control',
						'label' => false,
						'options' => $countryOptions,
						'default' => $selectedCountry,
						'url' => $baseUrl,
						'data-named-key' => 'country'
					));
				}
			?>
		</div>
	</div>
<?php endif ?>
