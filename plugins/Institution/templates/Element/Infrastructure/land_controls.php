<?php if (!empty($periodOptions) || !empty($typeOptions) || !empty($statusOptions)) : ?>
	<div class="toolbar-responsive panel-toolbar">
		<div class="toolbar-wrapper">
			<?php
				$url = [
					'plugin' => $this->request->getParam('plugin'),
				    'controller' => $this->request->getParam('controller'),
				    'action' => $this->request->getParam('action'),
				    'institutionId' => $this->request->getParam('institutionId')
				];
				if (!empty($this->request->getParam('pass'))) {
					$url = array_merge($url, $this->request->getParam('pass'));
				}

				$dataNamedGroup = [];
				if (!empty($this->request->getQuery())) {
					foreach ($this->request->getQuery() as $key => $value) {
						if (in_array($key, ['period_id', 'type', 'status'])) continue;
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
						'data-named-key' => 'period_id',
						'data-named-group' => 'type, status',
						'escape' => false
					];
					if (!empty($dataNamedGroup)) {
						$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
						$dataNamedGroup[] = 'period_id';
					}
					echo $this->Form->input('period_id', $inputOptions);
				}

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
