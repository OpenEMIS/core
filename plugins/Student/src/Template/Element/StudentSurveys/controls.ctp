<?php if (!empty($periodOptions) || !empty($formOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				$url = [
					'plugin' => $this->request->params['plugin'],
				    'controller' => $this->request->params['controller'],
				    'action' => $this->request->params['action']
				];
				if (!empty($this->request->pass)) {
					$url = array_merge($url, $this->request->pass);
				}

				$dataNamedGroup = [];
				if (!empty($this->request->query)) {
					foreach ($this->request->query as $key => $value) {
						if (in_array($key, ['period', 'form'])) continue;
						echo $this->Form->hidden($key, [
							'value' => $value,
							'data-named-key' => $key
						]);
						$dataNamedGroup[] = $key;
					}
				}

				$baseUrl = $this->Url->build($url);
				$template = $this->ControllerAction->getFormTemplate();
				$this->Form->templates($template);

				if (!empty($periodOptions)) {
					$inputOptions = [
						'class' => 'form-control',
						'label' => false,
						'options' => $periodOptions,
						'url' => $baseUrl,
						'data-named-key' => 'period',
						'escape' => false
					];
					if (!empty($dataNamedGroup)) {
						$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
						$dataNamedGroup[] = 'period';
					}
					echo $this->Form->input('academic_period', $inputOptions);
				}

				if (!empty($formOptions)) {
					$inputOptions = [
						'class' => 'form-control',
						'label' => false,
						'options' => $formOptions,
						'url' => $baseUrl,
						'data-named-key' => 'form',
						'escape' => false
					];
					if (!empty($dataNamedGroup)) {
						$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
						$dataNamedGroup[] = 'form';
					}
					echo $this->Form->input('survey_form', $inputOptions);
				}
			?>
		</div>
	</div>
<?php endif ?>
