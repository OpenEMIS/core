<?php if (!empty($typeOptions)) : ?>
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
						if ($key == 'type') continue;
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

				$inputOptions = [
					'class' => 'form-control',
					'label' => false,
					'options' => $typeOptions,
					'url' => $baseUrl,
					'data-named-key' => 'type',
					'escape' => false
				];
				if (!empty($dataNamedGroup)) {
					$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
				}
				echo $this->Form->input('infrastructure_type', $inputOptions);
			?>
		</div>
	</div>
<?php endif ?>
