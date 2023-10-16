<?php if (!empty($periodOptions) || !empty($typeOptions) || !empty($statusOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				$url = [
					'plugin' => $this->request->params['plugin'],
				    'controller' => $this->request->params['controller'],
				    'action' => $this->request->params['action'],
				    'institutionId' => $this->request->param('institutionId'),
				    'queryString' => $this->request->query('queryString')
				];
				if (!empty($this->request->pass)) {
					$url = array_merge($url, $this->request->pass);
				}

				$dataNamedGroup = [];
				if (!empty($this->request->query)) {
					foreach ($this->request->query as $key => $value) {
						if (in_array($key, ['period_id'])) {
							echo $this->Form->hidden($key, [
								'value' => $value,
								'data-named-key' => $key,
								'data-named-group' => 'type, status'
							]);
							$dataNamedGroup[] = $key;
						}
					}
				}

				$baseUrl = $this->Url->build($url);
				$template = $this->ControllerAction->getFormTemplate();
				$this->Form->templates($template);

				if (!empty($typeOptions)) {
					$inputOptions = [
						'class' => 'form-control',
						'label' => false,
						'options' => $typeOptions,
						'url' => $baseUrl,
						'data-named-key' => 'type',
						'data-named-group' => 'period_id, status',
						'escape' => false
					];
					if (!empty($dataNamedGroup)) {
						$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
						$dataNamedGroup[] = 'type';
					}
					echo $this->Form->input('type', $inputOptions);
				}

				if (!empty($statusOptions)) {
					$inputOptions = [
						'class' => 'form-control',
						'label' => false,
						'options' => $statusOptions,
						'url' => $baseUrl,
						'data-named-key' => 'status',
						'data-named-group' => 'type, period_id',
						'escape' => false
					];
					if (!empty($dataNamedGroup)) {
						$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
					}
					echo $this->Form->input('status', $inputOptions);
				}
			?>
		</div>
	</div>
<?php endif ?>
